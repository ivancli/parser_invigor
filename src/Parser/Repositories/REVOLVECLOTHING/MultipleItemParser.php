<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 19/06/2017
 * Time: 11:40 PM
 */

namespace IvanCLI\Parser\Repositories\REVOLVECLOTHING;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    const MULTIPLE_PROPERTY_PARSER_XPATH = '//*[@itemtype="http://schema.org/Product"]';

    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productInfo;

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
        $skuMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        $xpathMeta = $this->options->filter(function ($option) {
            return $option->element == 'XPATH';
        })->first();
        $xpath = $xpathMeta->value;


        if (!is_null($skuMeta)) {
            $sku = $skuMeta->value;
            if (!is_null($this->content) && !empty($this->content)) {
                $crawler = new Crawler($this->content);
                $xpathNodes = $crawler->filterXPath(self::MULTIPLE_PROPERTY_PARSER_XPATH);
                $xpathNodes->each(function (Crawler $itemNode) use ($sku, $xpath) {
                    $skuNodes = $itemNode->filterXPath('//*[@itemprop="sku"][@content="' . $sku . '"]');
                    if ($skuNodes->count() > 0) {
                        $xpathResults = $itemNode->filterXPath($xpath);
                        foreach ($xpathResults as $xpathResult) {
                            if ($xpathResult->nodeValue) {
                                $content = $xpathResult->nodeValue;
                            } else {
                                $content = $xpathResult->textContent;
                            }
                            $this->extractions[] = $content;
                        }
                    }
                });
            }
        }

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
}