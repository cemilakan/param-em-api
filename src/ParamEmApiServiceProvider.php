<?php

namespace Param\EmApi;

use Illuminate\Support\ServiceProvider;

class ParamEmApiServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/param_em_api.php', 'param_em_api');

        $this->app->singleton(ParamEmApi::class, function ($app) {
            return new ParamEmApi();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/param_em_api.php' => config_path('param_em_api.php'),
        ], 'config');
    }
}
