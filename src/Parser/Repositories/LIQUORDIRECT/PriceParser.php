<?php

/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 20/06/2017
 * Time: 1:48 PM
 */

namespace IvanCLI\Parser\Repositories\LIQUORDIRECT;

use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class PriceParser implements ParserContract
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
        $xpathConfs = $this->options->filter(function ($option) {
            return $option->element == 'XPATH';
        });

        $price = "";
        $counter = 0;
        foreach ($xpathConfs as $index => $xpathConf) {
            if ($counter > 0) {
                $price .= ".";
            }
            $price .= $this->__extract($xpathConf->value);
            $counter++;
        }
        $this->extractions [] = $price;
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract($xpath)
    {
        $crawler = new Crawler($this->content);
        $xpathNodes = $crawler->filterXPath($xpath);
        $extractions = [];
        if (count($xpathNodes) == 0) {
            return false;
        }
        foreach ($xpathNodes as $xpathNode) {
            if ($xpathNode->nodeValue) {
                $extraction = $xpathNode->nodeValue;
            } else {
                $extraction = $xpathNode->textContent;
            }
            $extraction = str_replace("\r\n", '', $extraction);
            return $extraction;
        }
        return $extractions;
    }
}