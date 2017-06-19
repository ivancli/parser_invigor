<?php

namespace IvanCLI\Parser\Repositories\IKEA;

use IvanCLI\Parser\Contracts\ParserContract;

/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 19/06/2017
 * Time: 2:40 PM
 */
class MultipleItemParser implements ParserContract
{
    const PRODUCT_DATA_REGEX = '#var jProductData \= (.*?)\;#';

    protected $content;
    protected $options;
    protected $extractions = [];

    protected $products = [];
    protected $attributes = [];

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
        $partNumberConf = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();
        if (!is_null($partNumberConf)) {
            $partNumber = $partNumberConf->value;
            $this->__getProductInfo();


            if (isset($this->products) && is_array($this->products)) {
                $matchedProducts = array_filter($this->products, function ($product) use ($partNumber) {
                    return $product->partNumber == $partNumber;
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

    public function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            preg_match(self::PRODUCT_DATA_REGEX, $this->content, $matches);
            if (isset($matches[1])) {
                $productInfo = json_decode($matches[1]);
                if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                    $productInfo = $productInfo->product;
                    $this->attributes = $productInfo->attributes;
                    $this->products = $productInfo->items;
                }
            }
        }
    }
}