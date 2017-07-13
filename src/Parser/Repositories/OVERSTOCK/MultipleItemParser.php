<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 13/07/2017
 * Time: 9:58 AM
 */

namespace IvanCLI\Parser\Repositories\OVERSTOCK;


use IvanCLI\Parser\Contracts\ParserContract;

class MultipleItemParser implements ParserContract
{
    const OPTION_TYPE_1_REGEX = '#os.optionBreakout.options = (.*?);#';
    const OPTION_TYPE_2_REGEX = "#dropDownOptions: (.*?) ,#si";

    protected $content;
    protected $options;
    protected $extractions = [];

    protected $productOptions;

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
        $optionIdConf = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        if (!is_null($optionIdConf)) {

            $this->__getProductOptions();
            $optionId = $optionIdConf->value;
            if (!is_null($this->productOptions)) {

                $productOption = array_first(array_filter($this->productOptions, function ($productOption) use ($optionId) {
                    return $productOption->id == $optionId;
                }));
                if (!is_null($productOption)) {
                    $arrayConfs = $this->options->filter(function ($option) {
                        return $option->element == 'ARRAY';
                    });

                    /*check array configuration to locate property in $item */
                    foreach ($arrayConfs as $arrayConf) {
                        $array = $arrayConf->value;
                        $levels = explode('.', $array);
                        $attribute = $productOption;
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

    private function __getProductOptions()
    {

        if (!is_null($this->content)) {
            preg_match(self::OPTION_TYPE_1_REGEX, $this->content, $matches);
            if (isset($matches[1])) {
                $matchOptions = $matches[1];
                $matchOptions = trim($matchOptions);
            } else {
                preg_match(self::OPTION_TYPE_2_REGEX, $this->content, $matches);
                if (isset($matches[1])) {
                    $matchOptions = $matches[1];
                    $matchOptions = trim($matchOptions);
                }
            }
            if (isset($matchOptions) && !is_null($matchOptions)) {
                $productOptions = json_decode($matchOptions);
                if (!is_null($productOptions) && json_last_error() === JSON_ERROR_NONE) {
                    $this->productOptions = $productOptions;
                    return true;
                }
            }
        }
    }
}