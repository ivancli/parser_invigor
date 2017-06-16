<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 16/06/2017
 * Time: 11:35 AM
 */

namespace IvanCLI\Parser\Repositories\SEPHORA;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productInfo;

    const LD_JSON_XPATH = '//script[@type="application/ld+json"]';

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
        if(!is_null($skuMeta)){
            $sku = $skuMeta->value;
            $this->__getProductInfo();
            if (!is_null($this->productInfo)) {
                if (isset($this->productInfo->offers) && is_array($this->productInfo->offers)) {
                    $offers = $this->productInfo->offers;
                    $matchedOffers = array_filter($offers, function ($offer) use ($sku) {
                        return $offer->sku == $sku;
                    });
                    $matchedOffer = array_first($matchedOffers);

                    $this->extractions[] = $matchedOffer->price;

                    return $this->extractions;
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

    private function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            $crawler = new Crawler($this->content);
            $xpathNodes = $crawler->filterXPath(self::LD_JSON_XPATH);
            $productInfo = null;
            foreach ($xpathNodes as $xpathNode) {
                if ($xpathNode->nodeValue) {
                    $productInfo = $xpathNode->nodeValue;
                } else {
                    $productInfo = $xpathNode->textContent;
                }
                if (!is_null($productInfo) && !empty($productInfo)) {
                    $productInfo = json_decode($productInfo);
                    if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                        if (isset($productInfo->{'@type'}) && $productInfo->{'@type'} == 'Product') {
                            $this->productInfo = $productInfo;
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

}