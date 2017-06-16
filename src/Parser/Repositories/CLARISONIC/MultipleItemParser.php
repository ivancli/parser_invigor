<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 16/06/2017
 * Time: 2:21 PM
 */

namespace IvanCLI\Parser\Repositories\CLARISONIC;


use IvanCLI\Parser\Contracts\ParserContract;

class MultipleItemParser implements ParserContract
{
    const DATA_PID_XPATH = '//a[@class="swatchanchor"][@data-pid]';
    const PRODUCT_INFO_REGEX = '#app.page.setEeProductsOnPage\((.*?)\)\;#';

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
        $pidMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();
        if (!is_null($pidMeta)) {
            $pid = $pidMeta->value;
            $this->__getProductInfo();
            if (!is_null($this->productInfo)) {
                $matchedProductInfo = array_first(array_filter($this->productInfo, function ($pInfo) use ($pid) {
                    return isset($pInfo->$pid) && !is_null($pInfo->$pid);
                }));
                $productInfo = $matchedProductInfo->$pid;
                if (!is_null($productInfo)) {

                    $arrayConf = $this->options->filter(function ($option) {
                        return $option->element == 'ARRAY';
                    })->first();

                    if (!is_null($arrayConf)) {
                        $array = $arrayConf->value;
                        $levels = explode('.', $array);
                        $attribute = $productInfo;
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
        $this->productInfo = [];
        if (!is_null($this->content) && !empty($this->content)) {
            preg_match_all(self::PRODUCT_INFO_REGEX, $this->content, $matches);
            if (isset($matches[1])) {
                $productInfo = $matches[1];
                foreach ($productInfo as $pInfo) {
                    $formattedInfo = json_decode($pInfo);
                    if (!is_null($formattedInfo) && json_last_error() === JSON_ERROR_NONE) {
                        $this->productInfo[] = $formattedInfo;
                    }
                }
                return $this->productInfo;
            }
        }
        return false;
    }

}