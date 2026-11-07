<?php

namespace ScreenFeedback;

final readonly class FeedbackData
{
    public function __construct(
        public string $type,
        public string $message,
        public ?string $pageUrl = null,
        public ?string $screenshot = null,
    ) {}
}
