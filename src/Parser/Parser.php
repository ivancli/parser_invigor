<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 11/03/2017
 * Time: 3:02 PM
 */

namespace IvanCLI\Parser;


class Parser
{
    public $app;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     */
    public function __construct($app)
    {
        $this->app = $app;
    }
}