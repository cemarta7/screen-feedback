<?php

namespace ScreenFeedback\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use ScreenFeedback\SubmitFeedback;

class FeedbackWidget extends Component
{
    /** @param array<string, mixed> $payload */
    public function send(array $payload, SubmitFeedback $submit): bool
    {
        $submit->handle($payload, Auth::user());
        $this->resetErrorBag();

        return true;
    }

    public function render(): View
    {
        return view('screen-feedback::livewire.widget', [
            'types' => config('screen-feedback.types'),
            'maxMessageLength' => config('screen-feedback.max_message_length'),
            'maxScreenshotBytes' => config('screen-feedback.max_screenshot_bytes'),
        ]);
    }
}
