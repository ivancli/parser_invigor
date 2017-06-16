<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 16/06/2017
 * Time: 12:02 PM
 */

namespace IvanCLI\Parser\Repositories;


use IvanCLI\Parser\Contracts\ParserContract;

class RegexParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    /**
     * set content property
     * @param $content
     * @return void
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * set options property needed for extraction.
     * @param $options
     * @return void
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * extract data from provided content
     * @return mixed
     */
    public function extract()
    {
        $regex = $this->options['regex'];
        if (isset($regex) && !empty($regex)) {
            if (is_array($regex)) {
                foreach ($regex as $rg) {
                    $this->extractions [] = $this->__extract($rg);
                }
            } else {
                $this->extractions = $this->__extract($regex);
            }
        }
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract($regex)
    {
        $extractions = [];
        preg_match($regex, $this->content, $matches);
        if (isset($matches[1])) {
            $extractions[] = $matches[1];
        }
        return $extractions;
    }
}