<?php
/**
 * Created by PhpStorm.
 * User: Ivan
 * Date: 2/06/2017
 * Time: 10:46 PM
 */

namespace IvanCLI\Parser\Repositories\EBAY;


use IvanCLI\Parser\Contracts\ParserContract;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

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
        if (json_decode($this->content) == null && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        } else {
            $content = json_decode($this->content);
        }
        if (isset($content->items) && is_array($content->items)) {
            $itemIdConf = $this->options->filter(function ($option) {
                return $option->element == 'OPTION_VALUE';
            })->first();
            /*TODO check if id is available*/
            if (!is_null($itemIdConf)) {
                $itemId = $itemIdConf->value;
                $items = array_filter($content->items, function ($item) use ($itemId) {
                    return $item->itemId == $itemId;
                });

                $item = array_first($items);

                $arrayConf = $this->options->filter(function ($option) {
                    return $option->element == 'ARRAY';
                })->first();

                /*check array configuration to locate property in $item */
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
            }
        } else {
            $arrayConfs = $this->options->filter(function ($option) {
                return $option->element == 'ARRAY';
            });
            /*check array configuration to locate property in $item */
            foreach ($arrayConfs as $arrayConf) {
                $array = $arrayConf->value;
                $levels = explode('.', $array);
                $attribute = $content;
                foreach ($levels as $key) {
                    if (is_object($attribute)) {
                        if (isset($attribute->$key)) {
                            $attribute = $attribute->$key;
                        } else {
                            continue;
                        }
                    } elseif (is_array($attribute)) {
                        if (array_has($attribute, $key)) {
                            $attribute = array_get($attribute, $key);
                        } else {
                            continue;
                        }
                    }
                }
                if($attribute != $content){
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
}