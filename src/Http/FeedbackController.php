<?php

namespace ScreenFeedback\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ScreenFeedback\SubmitFeedback;

class FeedbackController
{
    public function __invoke(Request $request, SubmitFeedback $submit): JsonResponse
    {
        $submit->handle($request->only(['type', 'message', 'page_url', 'screenshot']), $request->user());

        return response()->json(['message' => 'Feedback submitted successfully.']);
    }
}
