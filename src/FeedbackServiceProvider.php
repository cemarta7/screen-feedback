<?php

namespace ScreenFeedback;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use ScreenFeedback\Contracts\FeedbackHandler;
use ScreenFeedback\Contracts\RecipientResolver;
use ScreenFeedback\Livewire\FeedbackWidget;

class FeedbackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/screen-feedback.php', 'screen-feedback');
        $this->app->bindIf(FeedbackHandler::class, EmailFeedbackHandler::class);
        $this->app->bindIf(RecipientResolver::class, ConfigRecipientResolver::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'screen-feedback');

        if (config('screen-feedback.register_routes')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if (class_exists(Livewire::class)) {
            Livewire::component('screen-feedback', FeedbackWidget::class);
        }

        $this->publishes([
            __DIR__.'/../config/screen-feedback.php' => config_path('screen-feedback.php'),
        ], 'screen-feedback-config');
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/screen-feedback'),
        ], 'screen-feedback-views');
    }
}
