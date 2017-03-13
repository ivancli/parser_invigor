<?php

/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 11/03/2017
 * Time: 2:53 PM
 */
namespace IvanCLI\Parser;

use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider
{

    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerParser();
    }

    private function registerParser()
    {
        $this->app->bind('parser', function ($app) {
            return new Parser($app);
        });
    }
}