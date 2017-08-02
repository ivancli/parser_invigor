<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 3/08/2017
 * Time: 9:31 AM
 */

namespace IvanCLI\Parser\Repositories\COSMEDE;


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
        $optionValue = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        $extractions = [];

        $crawler = new Crawler($this->content);
        $xpathNodes = $crawler->filterXPath("//tr[td[@class=\"product_name\" and contains(text(), \"{$optionValue->value}\")]]");

        $xpaths = $this->options->filter(function ($option) {
            return $option->element == 'XPATH';
        })->pluck('value');

        $xpathNodes->each(function (Crawler $xpathNode) use ($xpaths, &$extractions) {
            foreach ($xpaths as $xpath) {
                $targetNodes = $xpathNode->filterXPath($xpath);
                foreach ($targetNodes as $targetNode) {
                    if ($targetNode->nodeValue) {
                        $extraction = $targetNode->nodeValue;
                    } else {
                        $extraction = $targetNode->textContent;
                    }
                    $extractions[] = $extraction;
                }
            }
        });

        return $extractions;
    }
}