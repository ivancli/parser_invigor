<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 21/06/2017
 * Time: 4:18 PM
 */

namespace IvanCLI\Parser\Repositories\LIQUOREMPORIUM;


use App\Models\Crawler;
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
        $this->getAppData();

        $optionMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        $price = "";
        $counter = 0;

        if (!is_null($optionMeta)) {
            $optionId = $optionMeta->value;
            $item = array_filter($this->productInfo->managedProductItems, function ($item) use ($optionId) {
                return in_array($optionId, $item->optionsSelections);
            });
            $item = array_first($item);
            if (!is_null($item)) {
                $arrayMeta = $this->options->filter(function ($option) {
                    return $option->element == 'ARRAY';
                })->first();
                if (!is_null($arrayMeta)) {
                    $array = $arrayMeta->value;
                    $levels = explode('.', $array);
                    $attribute = $item;
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
                    return null;
                }
            }
        }


        $arrayMeta = $this->options->filter(function ($option) {
            return $option->element == 'ARRAY';
        })->first();
        if (!is_null($arrayMeta)) {
            $array = $arrayMeta->value;
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
            return null;
        }

        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    protected function getAppData()
    {
        preg_match('#\'productPageApp\', (.*?), \'#', $this->content, $matches);
        if (isset($matches[1])) {
            $appData = json_decode($matches[1]);
            if (!is_null($appData) && json_last_error() === JSON_ERROR_NONE) {
                if (isset($appData->appData) && isset($appData->appData->productPageData) && isset($appData->appData->productPageData->product)) {
                    $this->productInfo = $appData->appData->productPageData->product;
                }
            }
        }
    }
}