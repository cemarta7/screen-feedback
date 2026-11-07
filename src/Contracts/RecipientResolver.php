<?php

namespace ScreenFeedback\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use ScreenFeedback\FeedbackData;

interface RecipientResolver
{
    public function resolve(FeedbackData $feedback, Authenticatable $user): string;
}
