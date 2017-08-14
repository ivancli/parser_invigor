<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 14/08/2017
 * Time: 10:15 AM
 */

namespace IvanCLI\Parser\Repositories\USELL;


use IvanCLI\Parser\Contracts\ParserContract;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productInfo;

    const API_URL = 'http://www.usell.com/ajax/product/';

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

        if (!is_null($this->productInfo)) {

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
        }
        return $this->extractions;
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
        if (!is_null($this->content)) {
            preg_match('#productId: (.*?),#', $this->content, $productIdMatches);
            $productId = array_get($productIdMatches, 1);
            $conditionIdConf = $this->options->filter(function ($option) {
                return $option->element == 'OPTION_VALUE';
            })->first();

            if (!is_null($productId) && !is_null($conditionIdConf)) {
                $url = self::API_URL . $productId . '/' . $conditionIdConf->value . '/offer.json';
                $response = Curl::to($url)
                    ->returnResponseObject()
                    ->withOption("FOLLOWLOCATION", true)
                    ->get();

                if ($response->status == 200) {
                    $productInfo = $response->content;
                    $productInfo = json_decode($productInfo);
                    if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {
                        $this->productInfo = $productInfo;
                    }
                }
            }
        }
    }

}