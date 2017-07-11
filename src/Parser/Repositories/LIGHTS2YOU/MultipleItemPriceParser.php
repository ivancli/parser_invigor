<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 21/06/2017
 * Time: 2:31 PM
 */

namespace IvanCLI\Parser\Repositories\LIGHTS2YOU;


use IvanCLI\Parser\Contracts\ParserContract;
use IvanCLI\Parser\Parser;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemPriceParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productInfo;

    const PRODUCT_INFO_REGEX = '#var spConfig = new Product.Config\((.*?)\);#';

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

        $optionValueConfs = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        });


        $basePrice = floatval($this->productInfo->basePrice);

        foreach ($optionValueConfs as $optionValueConf) {
            $optionValue = $optionValueConf->value;

            foreach ($this->productInfo->attributes as $attribute) {
                foreach ($attribute->options as $option) {
                    if ($option->id == $optionValue) {
                        $basePrice += floatval($option->price);
                    }
                }
            }
        }
        $this->extractions[] = $basePrice;

        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
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