<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 12/07/2017
 * Time: 2:36 PM
 */

namespace IvanCLI\Parser\Repositories\BOOZEBUD;


use IvanCLI\Parser\Contracts\ParserContract;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $optionInfo;
    protected $productInfo;

    const OPTION_INFO_REGEX = '#var opConfig = new Product.Options\((.*?)\);#';
    const PRODUCT_INFO_REGEX = '#var optionsPrice = new Product.OptionsPrice\((.*?)\);#';

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
        $this->__getProductInfo();

        $skuConf = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        if (!is_null($skuConf) && !is_null($this->productInfo)) {

            $sku = $skuConf->value;
            $variants = collect($this->productInfo->variants);
            $variant = $variants->filter(function ($variant) use ($sku) {
                return $variant->sku == $sku;
            })->first();

            if (!is_null($variant)) {
                $arrayConfs = $this->options->filter(function ($option) {
                    return $option->element == 'ARRAY';
                });
                /*check array configuration to locate property in $item */
                foreach ($arrayConfs as $arrayConf) {
                    $array = $arrayConf->value;
                    $levels = explode('.', $array);
                    $attribute = $variant;
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

        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            $formattedInfo = json_decode($this->content);
            if (!is_null($formattedInfo) && json_last_error() === JSON_ERROR_NONE) {
                $this->productInfo = $formattedInfo;
            }
        }
    }
}