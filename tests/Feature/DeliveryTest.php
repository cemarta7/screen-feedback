<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ScreenFeedback\FeedbackData;
use ScreenFeedback\Jobs\SendFeedbackEmail;
use ScreenFeedback\Mail\FeedbackMail;

it('delivers attachments and deletes temporary screenshots after successful delivery', function () {
    Mail::fake();
    Storage::fake('local');
    Storage::disk('local')->put('screen-feedback/test.png', base64_decode(screenshotPng()));
    $job = new SendFeedbackEmail(new FeedbackData('issue', 'Hello'), 'support@example.com', 'Example', 'user@example.com', 'local', 'screen-feedback/test.png');
    $job->handle();
    Mail::assertSent(FeedbackMail::class, fn ($mail) => $mail->hasTo('support@example.com') && count($mail->attachments()) === 1);
    Storage::disk('local')->assertMissing('screen-feedback/test.png');
});

it('retains screenshots when email delivery fails so the job can retry', function () {
    Storage::fake('local');
    Storage::disk('local')->put('screen-feedback/test.png', base64_decode(screenshotPng()));
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Mail unavailable'));
    $job = new SendFeedbackEmail(new FeedbackData('issue', 'Hello'), 'support@example.com', 'Example', 'user@example.com', 'local', 'screen-feedback/test.png');
    expect(fn () => $job->handle())->toThrow(RuntimeException::class);
    Storage::disk('local')->assertExists('screen-feedback/test.png');
});

it('escapes comments and includes page context and reply address in email', function () {
    $mail = new FeedbackMail(new FeedbackData('issue', '<script>alert(1)</script>', 'https://example.com/page'), 'Example', 'user@example.com', 'local', null);
    $mail->assertSeeInHtml('&lt;script&gt;alert(1)&lt;/script&gt;', escape: false)
        ->assertSeeInHtml('https://example.com/page')
        ->assertDontSeeInHtml('<script>', escape: false);
    expect($mail->envelope()->replyTo[0]->address)->toBe('user@example.com');
});
