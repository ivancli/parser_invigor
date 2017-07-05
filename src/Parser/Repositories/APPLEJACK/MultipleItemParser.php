<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 5/07/2017
 * Time: 2:49 PM
 */

namespace IvanCLI\Parser\Repositories\APPLEJACK;


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
        $internalIdMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();
        if (!is_null($internalIdMeta)) {
            $internalId = $internalIdMeta->value;
            $this->__getProductInfo();
            if (!is_null($this->productInfo)) {
                $items = array_first($this->productInfo->items);
                if (!is_null($items) && isset($items->matrixchilditems_detail)) {
                    $items = $items->matrixchilditems_detail;
                    $items = collect($items);
                    $item = $items->filter(function ($item) use ($internalId) {
                        return $item->internalid == $internalId;
                    })->first();
                    if (!is_null($item)) {

                        $arrayConf = $this->options->filter(function ($option) {
                            return $option->element == 'ARRAY';
                        })->first();

                        if (!is_null($arrayConf)) {
                            $array = $arrayConf->value;
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
                        }
                        return $this->extractions;
                    }
                }
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

    private function __getProductInfo()
    {
        if (!is_null($this->content) && !empty($this->content)) {
            $formattedInfo = json_decode($this->content);

            if (!is_null($formattedInfo) && json_last_error() === JSON_ERROR_NONE) {
                $this->productInfo = $formattedInfo;
                return $this->productInfo;
            }
        }
        return false;
    }

}