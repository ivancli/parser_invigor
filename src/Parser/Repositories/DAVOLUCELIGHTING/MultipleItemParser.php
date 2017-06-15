<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 3/13/2017
 * Time: 4:27 PM
 */

namespace IvanCLI\Parser\Repositories\DAVOLUCELIGHTING;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
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
        $this->extractions = $this->__extract();
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract()
    {
        $optionValues = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->pluck('value');

        $extractions = [];

        preg_match('#var price=(.*?);#', $this->content, $matches);
        $basePrice = 0;
        if (isset($matches[1])) {
            $basePrice = $matches[1];
        }
        unset($matches);

        preg_match("#modifiers\[(\d+)\]#", $this->content, $matches);

        /* has optional items (charges additional fees)*/
        /* e.g.
         * bulb by itself $30
         * bulb with dimmable driver + $25 ($55 total)
         */
        if (count($matches) > 0) {
            unset($matches);
            foreach ($optionValues as $optionValue) {
                preg_match("#modifiers\[(\d+)\]\[$optionValue\]=\[(.*?),'\\$',\{\}\];#", $this->content, $matches);
                if (isset($matches[2])) {
                    $extractions[] = $basePrice + $matches[2];
                }
            }
        } else {
            /* item itself has different price from other options (different price as a whole) */
            /* e.g.
             * color white - $30
             * color warm white - $35
             */
            $counter = [];
            foreach ($optionValues as $optionValue) {
                preg_match_all('#variants\[(\d+?)\]\[1\]\[\d+\]=' . $optionValue . ';#', $this->content, $matches);
                if (isset($matches[1])) {
                    foreach ($matches[1] as $match) {
                        $counter[$match] = (!isset($counter[$match])) ? 1 : $counter[$match] + 1;
                    }
                }
            }
            if (count($counter) == 0) {
                return null;
            }

            $key = array_first(array_keys($counter, max($counter)));

            preg_match('#variants\[' . $key . '\]=\[\[(.*?),#', $this->content, $matches);
            if (isset($matches[1])) {
                $extractions [] = $matches[1];
            }
        }
        return $extractions;
    }
}