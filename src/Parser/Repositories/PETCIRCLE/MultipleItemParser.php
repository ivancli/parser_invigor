<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 16/06/2017
 * Time: 4:14 PM
 */

namespace IvanCLI\Parser\Repositories\PETCIRCLE;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
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

        $attributes = $this->options->filter(function ($option) {
            return $option->element == "ATTRIBUTE";
        });

        if (!is_null($skuMeta)) {
            $sku = $skuMeta->value;
            if (!is_null($this->content) && !empty($this->content)) {
                $crawler = new Crawler($this->content);
                $xpathNodes = $crawler->filterXPath("//*[@id='{$sku}']");
                foreach ($xpathNodes as $xpathNode) {
                    foreach ($attributes as $attribute) {
                        $result = $xpathNode->getAttribute($attribute->value);
                        $this->extractions[] = $result;
                    }
                }
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