<?php

namespace ScreenFeedback;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use ScreenFeedback\Contracts\RecipientResolver;

class ConfigRecipientResolver implements RecipientResolver
{
    public function resolve(FeedbackData $feedback, Authenticatable $user): string
    {
        $recipient = config('screen-feedback.recipient');

        if (! is_string($recipient) || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['type' => 'Feedback delivery is not configured.']);
        }

        return $recipient;
    }
}
