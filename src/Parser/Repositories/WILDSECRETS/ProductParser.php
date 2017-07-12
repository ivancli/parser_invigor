<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 4/07/2017
 * Time: 11:38 AM
 */

namespace IvanCLI\Parser\Repositories\WILDSECRETS;


use IvanCLI\Parser\Contracts\ParserContract;

class ProductParser implements ParserContract
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

        if (isset($this->productInfo) && !is_null($this->productInfo)) {

            $arrayConf = $this->options->filter(function ($option) {
                return $option->element == 'ARRAY';
            })->first();

            /*check array configuration to locate property in $item */
            if (!is_null($arrayConf)) {
                $array = $arrayConf->value;
                $levels = explode('.', $array);
                $attribute = $this->productInfo;
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

    /**
     * get extracted data
     * @return mixed
     */
    public function getExtractions()
    {
        return $this->extractions;
    }

    protected function __getProductInfo()
    {
        preg_match('#productDetailCtrl.init\((.*?), (.*?), \[\{#', $this->content, $matches);
        if (isset($matches[2])) {
            $productInfo = json_decode($matches[2]);
            if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                $this->productInfo = $productInfo;
            }
        }
    }
}