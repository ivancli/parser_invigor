<?php

/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 11/03/2017
 * Time: 2:57 PM
 */
namespace IvanCLI\Parser;

use Illuminate\Support\Facades\Facade;

class ParserFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'parser';
    }
}