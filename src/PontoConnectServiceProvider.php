<?php

namespace PontoConnect;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class PontoConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ponto-connect.php',
            'ponto-connect'
        );

        $this->app->singleton(Configuration::class, function (Application $app) {
            return Configuration::fromArray($app->make('config')->get('ponto-connect', []));
        });

        $this->app->singleton(PontoConnectManager::class, function (Application $app) {
            return new PontoConnectManager($app->make(Configuration::class));
        });

        $this->app->alias(PontoConnectManager::class, 'ponto-connect');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ponto-connect.php' => config_path('ponto-connect.php'),
            ], 'ponto-connect-config');
        }
    }
}
