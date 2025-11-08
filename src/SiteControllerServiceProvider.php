<?php

namespace Siteffects\SiteController;

use Illuminate\Support\ServiceProvider;

class SiteControllerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/config/site-controller.php' => config_path('site-controller.php'),
        ], 'config');

        // Load views from package
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'site-controller');

        // Register middleware alias (works in all versions)
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('site-status', SiteStatusMiddleware::class);
        }
    }

    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/config/site-controller.php', 'site-controller');
    }
}