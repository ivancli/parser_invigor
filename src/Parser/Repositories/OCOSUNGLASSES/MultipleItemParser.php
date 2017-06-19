<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 19/06/2017
 * Time: 1:37 PM
 */

namespace IvanCLI\Parser\Repositories\OCOSUNGLASSES;


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
        $mpnConf = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();
        if (!is_null($mpnConf)) {
            $mpn = $mpnConf->value;
            $this->__getProductInfo();
            if (isset($this->productInfo) && is_array($this->productInfo)) {
                $matchedProducts = array_filter($this->productInfo, function ($product) use ($mpn) {
                    return $product->mpn == $mpn;
                });
                $matchedProduct = array_first($matchedProducts);


                $arrayConf = $this->options->filter(function ($option) {
                    return $option->element == 'ARRAY';
                })->first();

                /*check array configuration to locate property in $item */
                if (!is_null($arrayConf)) {
                    $array = $arrayConf->value;
                    $levels = explode('.', $array);
                    $attribute = $matchedProduct;
                    foreach ($levels as $key) {
                        if (is_object($attribute)) {
                            if (isset($attribute->$key)) {
                                $attribute = $attribute->$key;
                            } else {
                                return false;
                            }
                        } elseif (is_array($attribute)) {
                            if (array_has($attribute, $key)) {
                                $attribute = array_get($attribute, $key);
                            } else {
                                return false;
                            }
                        }
                    }
                    $this->extractions[] = $attribute;
                }

                return $this->extractions;
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
                    if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE && is_array($productInfo)) {
                        $listOfProductInfo = array_first($productInfo);
                        if (is_array($listOfProductInfo)) {
                            $firstProductInfo = array_first($listOfProductInfo);
                            if (isset($firstProductInfo->{'@type'}) && $firstProductInfo->{'@type'} == 'Product') {
                                $this->productInfo = $listOfProductInfo;
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

}