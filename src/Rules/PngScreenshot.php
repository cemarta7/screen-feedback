<?php

namespace ScreenFeedback\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PngScreenshot implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $bytes = is_string($value) ? base64_decode($value, true) : false;

        if ($bytes === false || strlen($bytes) > config('screen-feedback.max_screenshot_bytes')) {
            $fail('The screenshot is invalid or too large.');

            return;
        }

        $image = @getimagesizefromstring($bytes);

        if ($image === false || $image[2] !== IMAGETYPE_PNG || config('screen-feedback.max_screenshot_pixels') < $image[0] * $image[1]) {
            $fail('The screenshot must be a PNG image within the allowed dimensions.');
        }
    }
}
