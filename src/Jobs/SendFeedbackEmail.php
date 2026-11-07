<?php

namespace ScreenFeedback\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ScreenFeedback\FeedbackData;
use ScreenFeedback\Mail\FeedbackMail;

class SendFeedbackEmail implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public function __construct(
        public FeedbackData $feedback,
        public string $recipient,
        public string $userName,
        public string $userEmail,
        public string $disk,
        public ?string $path,
    ) {
        $this->afterCommit();
    }

    public function handle(): void
    {
        Mail::to($this->recipient)->send(new FeedbackMail(
            $this->feedback, $this->userName, $this->userEmail, $this->disk, $this->path,
        ));

        if ($this->path !== null) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
