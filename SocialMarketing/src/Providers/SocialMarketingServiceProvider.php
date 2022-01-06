<?php

namespace Secureweb\Socialmarketing\Providers;

use Illuminate\Support\ServiceProvider;

class SocialMarketingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('socialmarketing', function($app){
            return new SocialMarketing;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/socialmarketing.php' => config_path('socialmarketing.php'),
        ], 'socialmarketing-config');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
