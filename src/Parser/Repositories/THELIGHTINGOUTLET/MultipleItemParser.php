<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 12/07/2017
 * Time: 9:48 AM
 */

namespace IvanCLI\Parser\Repositories\THELIGHTINGOUTLET;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    const PRODUCT_INFO_XPATH = '//*[@data-product_variations]';

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
        $optionIdMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();


        if (!is_null($optionIdMeta)) {
            $optionId = $optionIdMeta->value;
            $this->__getProductInfo();
            if (!is_null($this->productInfo)) {
                $matchedProductOption = array_first(array_filter($this->productInfo, function ($productOption) use ($optionId) {
                    return isset($productOption->sku) && $productOption->sku == $optionId;
                }));


                if (!is_null($matchedProductOption)) {

                    $arrayConf = $this->options->filter(function ($option) {
                        return $option->element == 'ARRAY';
                    })->first();

                    if (!is_null($arrayConf)) {
                        $array = $arrayConf->value;
                        $levels = explode('.', $array);
                        $attribute = $matchedProductOption;
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
            $productInfoNode = $crawler->filterXPath(self::PRODUCT_INFO_XPATH)->first();
            if (!is_null($productInfoNode)) {
                $productInfo = $productInfoNode->attr('data-product_variations');
                $productInfo = html_entity_decode($productInfo);

                $formattedInfo = json_decode($productInfo);
                if (!is_null($formattedInfo) && json_last_error() === JSON_ERROR_NONE) {
                    $this->productInfo = $formattedInfo;
                    return $this->productInfo;
                }
            }
        }
        return false;
    }

}