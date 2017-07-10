<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 10/07/2017
 * Time: 10:37 AM
 */

namespace IvanCLI\Parser\Repositories\WESTELM;


use IvanCLI\Parser\Contracts\ParserContract;

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
        $this->__getProductInfo();
        if (!is_null($this->productInfo) && isset($this->productInfo->results)) {
            $internalIdConf = $this->options->filter(function ($option) {
                return $option->element == 'OPTION_VALUE';
            })->first();


            /*check array configuration to locate property in $item */
            if (!is_null($internalIdConf)) {
                $internalId = $internalIdConf->value;

                $products = collect($this->productInfo->results);
                $product = $products->filter(function ($product) use ($internalId) {
                    return $product->internalid == $internalId;
                })->first();
                if (!is_null($product)) {
                    $arrayConfs = $this->options->filter(function ($option) {
                        return $option->element == 'ARRAY';
                    });
                    foreach ($arrayConfs as $arrayConf) {
                        $array = $arrayConf->value;
                        $levels = explode('.', $array);
                        $attribute = $product;
                        $valid = true;
                        foreach ($levels as $key) {
                            if (is_object($attribute)) {
                                if (isset($attribute->$key)) {
                                    $attribute = $attribute->$key;
                                } else {
                                    $valid = false;
                                    break;
                                }
                            } elseif (is_array($attribute)) {
                                if (array_has($attribute, $key)) {
                                    $attribute = array_get($attribute, $key);
                                } else {
                                    $valid = false;
                                    break;
                                }
                            } else {
                                $valid = false;
                                break;
                            }
                        }
                        if ($valid == true) {
                            $this->extractions[] = $attribute;
                        }
                    }
                }
            }

            return $this->extractions;
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

    private function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            $productInfo = json_decode($this->content);
            if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                $this->productInfo = array_first($productInfo);
                return true;
            }
        }
        return false;
    }

}