<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 16/06/2017
 * Time: 2:47 PM
 */

namespace IvanCLI\Parser\Repositories\FIRSTCHOICELIQUOR;


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
     * @return mixed
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * set options property needed for extraction.
     * @param $options
     * @return mixed
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

    /**
     * get extracted data
     * @return mixed
     */
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

        $crawler = new Crawler($this->content);
        foreach ($optionValues as $optionValue) {
            $xpathNodes = $crawler->filterXPath("//*[@class=\"priceStockDetail\"]//dt[text()=\"{$optionValue}\"]/following-sibling::dd[1]");

            if (count($xpathNodes) == 0) {
                continue;
            }

            foreach ($xpathNodes as $xpathNode) {
                if ($xpathNode->nodeValue) {
                    $extraction = $xpathNode->nodeValue;
                } else {
                    $extraction = $xpathNode->textContent;
                }
                $extractions[] = $extraction;
            }
        }

        return $extractions;
    }
}