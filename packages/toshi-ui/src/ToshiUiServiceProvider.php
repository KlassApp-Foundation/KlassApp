<?php

namespace KlassApp\ToshiUi;

use Illuminate\Support\ServiceProvider;

class ToshiUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'toshi-ui');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/toshi-ui'),
        ], 'toshi-ui-views');

        $this->publishes([
            __DIR__ . '/../resources/css' => public_path('vendor/toshi-ui'),
        ], 'toshi-ui-css');
    }

    public function register(): void
    {
    }
}
