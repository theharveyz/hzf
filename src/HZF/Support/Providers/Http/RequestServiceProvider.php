<?php

/**
 *
 */
namespace HZF\Support\Providers\Http;

use HZF\Support\ServiceProvider;
use HZF\Config\Config;

class RequestServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('request');
    }
}
