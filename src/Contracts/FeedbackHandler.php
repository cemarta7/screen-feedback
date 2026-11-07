<?php

namespace ScreenFeedback\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use ScreenFeedback\FeedbackData;

interface FeedbackHandler
{
    public function handle(FeedbackData $feedback, Authenticatable $user): void;
}
