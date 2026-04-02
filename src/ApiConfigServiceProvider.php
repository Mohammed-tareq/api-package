<?php


namespace MohammedTareq\ApiConfig;

use Illuminate\Support\ServiceProvider;

class ApiConfigServiceProvider  extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/api.php', 'api'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
       
        $this->publishes([
            __DIR__ . '/config/api.php' => config_path('api.php'),
        ], 'config');
    }
}