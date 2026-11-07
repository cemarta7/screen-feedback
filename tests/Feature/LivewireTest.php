<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ScreenFeedback\Jobs\SendFeedbackEmail;

beforeEach(function () {
    Queue::fake();
    Storage::fake('local');
});

it('renders the registered Livewire component', function () {
    Livewire::test('screen-feedback')->assertSee('Share Feedback')->assertSee('Message');
});

it('submits feedback through the Livewire action', function () {
    Livewire::actingAs($this->user())->test('screen-feedback')
        ->call('send', ['type' => 'issue', 'message' => 'Livewire comment', 'screenshot' => screenshotPng()])
        ->assertHasNoErrors()->assertReturned(true);
    Queue::assertPushed(SendFeedbackEmail::class, fn ($job) => $job->feedback->message === 'Livewire comment');
});

it('checks authorization on every Livewire action', function () {
    Livewire::test('screen-feedback')->call('send', ['type' => 'issue', 'message' => 'Guest'])->assertForbidden();
    Queue::assertNothingPushed();
});

it('shares validation and rate limits between HTTP and Livewire', function () {
    config(['screen-feedback.submissions_per_minute' => 1]);
    $this->actingAs($this->user());
    Livewire::test('screen-feedback')->call('send', ['type' => 'issue', 'message' => 'Hello', 'screenshot' => 'bad'])->assertHasErrors('screenshot');
    $this->postJson(route('screen-feedback.store'), ['type' => 'issue', 'message' => 'First'])->assertOk();
    Livewire::test('screen-feedback')->call('send', ['type' => 'issue', 'message' => 'Second'])->assertHasErrors('message');
    Queue::assertPushed(SendFeedbackEmail::class, 1);
});
