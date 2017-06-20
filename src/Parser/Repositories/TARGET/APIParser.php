<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 20/06/2017
 * Time: 10:00 AM
 */

namespace IvanCLI\Parser\Repositories\TARGET;


use IvanCLI\Parser\Contracts\ParserContract;

class APIParser implements ParserContract
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
        $this->__getProductInfo();

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
            return $this->extractions;
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
            $productInfo = json_decode($this->content);
            if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                $this->productInfo = $productInfo;
                return true;
            }
        }
        return false;
    }
}