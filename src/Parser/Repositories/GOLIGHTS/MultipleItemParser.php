<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 4/07/2017
 * Time: 2:17 PM
 */

namespace IvanCLI\Parser\Repositories\GOLIGHTS;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    const PRODUCT_OPTIONS_XPATH = '//*[@class="product__summary"]//div[@itemprop="offers"][not(contains(@class, "offer-info"))]';


    protected $content;
    protected $options;
    protected $extractions = [];

    protected $products = [];

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
        $option = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();
        $sku = $option->value;

        if (!is_null($this->content) && !empty($this->content)) {
            $crawler = new Crawler($this->content);
            $productOptionNodes = $crawler->filterXPath(self::PRODUCT_OPTIONS_XPATH);
            $productNode = null;
            $productOptionNodes->each(function (Crawler $productOptionNode) use ($sku, &$productNode) {
                $skuNodes = $productOptionNode->filterXPath('//*[@itemprop="sku"][@content="' . $sku . '"]');
                if ($skuNodes->count() > 0) {
                    $productNode = $productOptionNode;
                }
            });
            if (!is_null($productNode)) {
                $xpathConfs = $this->options->filter(function ($option) {
                    return $option->element == 'XPATH';
                });
                $extractions = [];
                foreach ($xpathConfs as $xpathConf) {

                    $xpathNodes = $productNode->filterXPath($xpathConf->value);
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
                $this->extractions = $extractions;
            }
        }
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