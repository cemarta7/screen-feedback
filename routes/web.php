<?php

use Illuminate\Support\Facades\Route;
use ScreenFeedback\Http\FeedbackController;

Route::post(config('screen-feedback.route'), FeedbackController::class)
    ->middleware(config('screen-feedback.middleware'))
    ->name('screen-feedback.store');
