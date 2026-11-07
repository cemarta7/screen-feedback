<?php

namespace ScreenFeedback\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/** Loaded only by Testbench, never discovered by consuming applications. */
class BrowserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config([
            'auth.defaults.guard' => 'demo',
            'auth.guards.demo' => ['driver' => 'demo'],
            'mail.default' => 'array',
            'queue.default' => 'sync',
            'screen-feedback.recipient' => 'support@example.com',
            'screen-feedback.submissions_per_minute' => 100,
        ]);
    }

    public function boot(): void
    {
        Auth::viaRequest('demo', fn () => new GenericUser(['id' => 1, 'name' => 'Demo User', 'email' => 'demo@example.com']));
        $this->loadViewsFrom(__DIR__.'/fixtures', 'feedback-tests');
        Route::middleware('web')->get('/demo', fn () => view('feedback-tests::livewire'));
    }
}
