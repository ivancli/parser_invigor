<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 11/07/2017
 * Time: 4:54 PM
 */

namespace IvanCLI\Parser\Repositories\BESTBUYLIGHTING;


use IvanCLI\Parser\Contracts\ParserContract;

class MultipleItemPriceParser implements ParserContract
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
        $this->__getOptionInfo();

        $optionValueConfs = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        });

        if(!is_null($this->productInfo) && isset($this->productInfo->productPrice)){
            $basePrice = $this->productInfo->productPrice;

            foreach ($optionValueConfs as $optionValueConf) {
                $optionValue = $optionValueConf->value;

                foreach ($this->optionInfo as $select) {
                    foreach ($select as $optionId => $option) {
                        if ($optionId == $optionValue) {
                            $basePrice += floatval($option->price);
                        }
                    }
                }
            }
            $this->extractions[] = $basePrice;
        }

        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __getOptionInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            preg_match(self::OPTION_INFO_REGEX, $this->content, $matches);
            if (isset($matches[1])) {
                $optionInfo = json_decode($matches[1]);
                if (!is_null($optionInfo) && json_last_error() === JSON_ERROR_NONE) {
                    $this->optionInfo = $optionInfo;
                    return $this->optionInfo;
                }
            }
        }
        return null;
    }

    private function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            preg_match(self::PRODUCT_INFO_REGEX, $this->content, $matches);
            if (isset($matches[1])) {
                $productInfo = json_decode($matches[1]);
                if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                    $this->productInfo = $productInfo;
                    return $this->productInfo;
                }
            }
        }
        return null;
    }
}