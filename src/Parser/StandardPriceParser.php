<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 5/07/2017
 * Time: 9:13 AM
 */

namespace IvanCLI\Parser\Repositories;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class StandardPriceParser implements ParserContract
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
        foreach ($xpathConfs as $xpathConf) {
            $xp = $xpathConf->value;
            $extractions = $this->__extract($xp);
            foreach ($extractions as $index => $extraction) {
                $extractions[$index] = number_format(floatval($extraction), 2);
            }
            $this->extractions = $extractions;
        }
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract($xpath)
    {
        $extractions = [];
        if (!is_null($this->content) && $this->content != false) {
            $crawler = new Crawler($this->content);
            $xpathNodes = $crawler->filterXPath($xpath);
            if (count($xpathNodes) == 0) {
                return false;
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