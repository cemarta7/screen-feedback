<?php

namespace ScreenFeedback\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use ScreenFeedback\FeedbackData;

class FeedbackMail extends Mailable
{
    public function __construct(
        public FeedbackData $feedback,
        public string $userName,
        public string $userEmail,
        public string $disk,
        public ?string $path,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: filter_var($this->userEmail, FILTER_VALIDATE_EMAIL) ? [new Address($this->userEmail, $this->userName)] : [],
            subject: '['.config('app.name').'] '.$this->feedback->type.' feedback',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'screen-feedback::email');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return $this->path === null ? [] : [
            Attachment::fromStorageDisk($this->disk, $this->path)->as('screenshot.png')->withMime('image/png'),
        ];
    }
}
