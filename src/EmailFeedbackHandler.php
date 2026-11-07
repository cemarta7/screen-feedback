<?php

namespace ScreenFeedback;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ScreenFeedback\Contracts\FeedbackHandler;
use ScreenFeedback\Contracts\RecipientResolver;
use ScreenFeedback\Jobs\SendFeedbackEmail;
use Throwable;

class EmailFeedbackHandler implements FeedbackHandler
{
    public function __construct(private RecipientResolver $recipients) {}

    public function handle(FeedbackData $feedback, Authenticatable $user): void
    {
        $recipient = $this->recipients->resolve($feedback, $user);
        $disk = (string) config('screen-feedback.disk');
        $path = null;

        if ($feedback->screenshot !== null && $feedback->screenshot !== '') {
            $path = 'screen-feedback/'.Str::uuid().'.png';
            $bytes = base64_decode($feedback->screenshot, true);
            if ($bytes === false || ! Storage::disk($disk)->put($path, $bytes, ['visibility' => 'private'])) {
                throw new RuntimeException('Unable to store the feedback screenshot.');
            }
        }

        try {
            SendFeedbackEmail::dispatch(
                new FeedbackData($feedback->type, $feedback->message, $feedback->pageUrl),
                $recipient,
                (string) data_get($user, 'name', 'User'),
                (string) data_get($user, 'email', ''),
                $disk,
                $path,
            )->onQueue((string) config('screen-feedback.queue'));
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }
}
