<?php

namespace ScreenFeedback\Tests;

use Illuminate\Auth\GenericUser;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ScreenFeedback\FeedbackServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, FeedbackServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=');
        $app['config']->set('screen-feedback.recipient', 'support@example.com');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from', ['address' => 'app@example.com', 'name' => 'Demo']);
    }

    protected function user(): GenericUser
    {
        return new GenericUser(['id' => 42, 'name' => 'Example User', 'email' => 'user@example.com']);
    }
}
