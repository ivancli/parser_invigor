<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 5/07/2017
 * Time: 5:45 PM
 */

namespace IvanCLI\Parser\Repositories\COLES;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

class PriceParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productInfo;

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

        $arrayConfs = $this->options->filter(function ($option) {
            return $option->element == 'ARRAY';
        });
        /*check array configuration to locate property in $item */
        foreach ($arrayConfs as $arrayConf) {
            $array = $arrayConf->value;
            $levels = explode('.', $array);
            $attribute = $this->productInfo;
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
        foreach ($this->extractions as $index => $extraction) {
            $this->extractions[$index] = number_format(floatval($extraction), 2);
        }
        return $this->extractions;
    }

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