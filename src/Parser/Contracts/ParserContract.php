<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 11/03/2017
 * Time: 3:23 PM
 */

namespace IvanCLI\Crawler\Contracts;


interface ParserContract
{

    /**
     * set content property
     * @param $content
     * @return mixed
     */
    public function setContent($content);

    /**
     * set options property needed for extraction.
     * @param $options
     * @return mixed
     */
    public function setOptions($options);

    /**
     * extract data from provided content
     * @return mixed
     */
    public function extract();
}