<?php

namespace ScreenFeedback;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ScreenFeedback\Contracts\FeedbackHandler;
use ScreenFeedback\Rules\PngScreenshot;

class SubmitFeedback
{
    public function __construct(private FeedbackHandler $handler) {}

    /** @param array<string, mixed> $input */
    public function handle(array $input, ?Authenticatable $user): void
    {
        abort_if($user === null, 403);

        if (isset($input['message']) && is_string($input['message'])) {
            $input['message'] = trim($input['message']);
        }

        $data = Validator::make($input, [
            'type' => ['required', 'string', Rule::in(array_keys(config('screen-feedback.types')))],
            'message' => ['required', 'string', 'max:'.config('screen-feedback.max_message_length')],
            'page_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'screenshot' => ['bail', 'nullable', 'string', 'max:'.(4 * (int) ceil(config('screen-feedback.max_screenshot_bytes') / 3)), new PngScreenshot],
        ])->validate();

        $key = 'screen-feedback:'.hash('sha256', get_class($user).':'.$user->getAuthIdentifier());

        if (RateLimiter::tooManyAttempts($key, (int) config('screen-feedback.submissions_per_minute'))) {
            throw ValidationException::withMessages(['message' => 'Too many submissions. Please try again in a minute.']);
        }

        RateLimiter::hit($key, 60);

        $this->handler->handle(new FeedbackData(
            $data['type'], $data['message'], $data['page_url'] ?? null, $data['screenshot'] ?? null,
        ), $user);
    }
}
