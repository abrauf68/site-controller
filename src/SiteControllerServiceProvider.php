<?php

namespace Siteffects\SiteController;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

class SiteControllerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // ---- Publish config -------------------------------------------------
        $this->publishes([
            __DIR__.'/config/site-controller.php' => config_path('site-controller.php'),
        ], 'site-controller-config');

        // ---- Load views ----------------------------------------------------
        $this->loadViewsFrom(__DIR__.'/resources/views', 'site-controller');

        // ---- Middleware alias -----------------------------------------------
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('site-status', SiteStatusMiddleware::class);
        }

        // ---- Auto-run key generation after publish -------------------------
        $this->app->afterResolving('command.vendor.publish', function () {
            $this->app->booted(function () {
                $argv = $_SERVER['argv'] ?? [];
                if (in_array('--tag=site-controller-config', $argv) ||
                    in_array('--tag=config', $argv)) {
                    Artisan::call('site-controller:generate-key');
                }
            });
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/site-controller.php', 'site-controller');

        // Register console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Siteffects\SiteController\Console\Commands\GenerateSiteControllerKey::class,
            ]);
        }
    }
}