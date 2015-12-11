<?php

/**
 *
 */
namespace HZF\Support\Providers;

use HZF\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('config');
    }

    public function boot()
    {
    	var_dump($this->app->config());
    }
}
