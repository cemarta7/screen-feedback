<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use ScreenFeedback\Contracts\FeedbackHandler;
use ScreenFeedback\Contracts\RecipientResolver;
use ScreenFeedback\FeedbackData;
use ScreenFeedback\Jobs\SendFeedbackEmail;

beforeEach(function () {
    Queue::fake();
    Storage::fake('local');
});

it('rejects guests', function () {
    $this->postJson(route('screen-feedback.store'), ['type' => 'issue', 'message' => 'Hello'])->assertUnauthorized();
    Queue::assertNothingPushed();
});

it('queues a validated screenshot with server supplied identity and recipient', function () {
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'issue', 'message' => '  Needs attention  ', 'screenshot' => screenshotPng(),
        'page_url' => 'https://example.com/dashboard', 'recipient' => 'attacker@example.com', 'user_email' => 'spoof@example.com',
    ])->assertOk();

    Queue::assertPushed(SendFeedbackEmail::class, function ($job) {
        Storage::disk('local')->assertExists($job->path);
        expect(Storage::disk('local')->get($job->path))->toBe(base64_decode(screenshotPng()));

        return $job->recipient === 'support@example.com' && $job->userEmail === 'user@example.com'
            && $job->feedback->message === 'Needs attention' && $job->feedback->screenshot === null;
    });
});

it('allows feedback without a screenshot', function () {
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'information', 'message' => 'Hello',
    ])->assertOk();
    Queue::assertPushed(SendFeedbackEmail::class, fn ($job) => $job->path === null);
});

it('rejects invalid payloads without storing or queueing', function ($field, $value) {
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        ...['type' => 'issue', 'message' => 'Hello'], $field => $value,
    ])->assertJsonValidationErrors($field);
    Queue::assertNothingPushed();
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
})->with([
    ['message', '   '], ['message', str_repeat('x', 5001)], ['type', 'unknown'],
    ['screenshot', '%%%'], ['screenshot', base64_encode('not a PNG')],
    ['screenshot', base64_encode(str_repeat('x', 2_000_001))], ['page_url', 'javascript:alert(1)'],
]);

it('rejects image dimensions beyond the configured limit', function () {
    config(['screen-feedback.max_screenshot_pixels' => 0]);
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'issue', 'message' => 'Hello', 'screenshot' => screenshotPng(),
    ])->assertJsonValidationErrors('screenshot');
});

it('uses a host provided recipient resolver', function () {
    $this->app->bind(RecipientResolver::class, fn () => new class implements RecipientResolver
    {
        public function resolve(FeedbackData $feedback, Authenticatable $user): string
        {
            return $feedback->type === 'information' ? 'admin@example.com' : 'support@example.com';
        }
    });
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), ['type' => 'information', 'message' => 'Hello'])->assertOk();
    Queue::assertPushed(SendFeedbackEmail::class, fn ($job) => $job->recipient === 'admin@example.com');
});

it('allows a host handler to replace persistence and delivery', function () {
    $handler = Mockery::mock(FeedbackHandler::class);
    $handler->shouldReceive('handle')->once()->withArgs(fn (FeedbackData $data, $user) => $data->message === 'Hello' && $user->getAuthIdentifier() === 42);
    $this->app->instance(FeedbackHandler::class, $handler);
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), ['type' => 'issue', 'message' => 'Hello'])->assertOk();
    Queue::assertNothingPushed();
});

it('rejects missing recipient configuration before storing screenshots', function () {
    config(['screen-feedback.recipient' => null]);
    $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'issue', 'message' => 'Hello', 'screenshot' => screenshotPng(),
    ])->assertJsonValidationErrors('type');
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('rate limits submissions', function () {
    config(['screen-feedback.submissions_per_minute' => 1]);
    $this->actingAs($this->user());
    $this->postJson(route('screen-feedback.store'), ['type' => 'issue', 'message' => 'First'])->assertOk();
    $this->postJson(route('screen-feedback.store'), ['type' => 'issue', 'message' => 'Second'])->assertJsonValidationErrors('message');
    Queue::assertPushed(SendFeedbackEmail::class, 1);
});

it('cleans up screenshots if dispatch fails', function () {
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Queue unavailable'));
    $this->app->instance(Dispatcher::class, $dispatcher);
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'issue', 'message' => 'Hello', 'screenshot' => screenshotPng(),
    ]))->toThrow(RuntimeException::class, 'Queue unavailable');

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('does not queue mail when screenshot storage fails', function () {
    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('put')->once()->andReturnFalse();
    Storage::shouldReceive('disk')->with('local')->andReturn($disk);
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($this->user())->postJson(route('screen-feedback.store'), [
        'type' => 'issue', 'message' => 'Hello', 'screenshot' => screenshotPng(),
    ]))->toThrow(RuntimeException::class, 'Unable to store');

    Queue::assertNothingPushed();
});
