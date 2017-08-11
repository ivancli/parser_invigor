<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 11/08/2017
 * Time: 1:31 PM
 */

namespace IvanCLI\Parser\Repositories\GAZELLE;


use IvanCLI\Parser\Contracts\ParserContract;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $utf8 = "%E2%9C%93";
    protected $authenticity_token;
    protected $product_id;
    protected $email = "Email+Address";
    protected $sell_another_device;
    protected $imei_input_field;
    protected $timestamp;
    protected $promo;
    protected $alternate_value;
    protected $n = 7;

    const API_URL = 'https://www.gazelle.com/products/';


    const AUTHENTICITY_TOKEN_XPATH = '//*[@name="csrf-token"]/@content';
    const PRODUCT_ID_XPATH = '//*[@name="product_id"]/@value';
    const CONDITION_KEY_XPATH = '//*[@id="calculator_condition"]/@name';

    const DATA_CONTENT_XPATH = '//*[@data-content]';

    protected $headers = [
        'Accept-Language: en-us',
        'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15',
        'Connection: Keep-Alive',
        'Cache-Control: no-cache',
    ];

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
        if (!is_null($this->productInfo)) {

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
        if (!is_null($this->content)) {
            $crawler = new Crawler($this->content);
            $this->authenticity_token = $this->__getElementValue($crawler, self::AUTHENTICITY_TOKEN_XPATH);
            $this->product_id = $this->__getElementValue($crawler, self::PRODUCT_ID_XPATH);
            $conditionCode = $this->__getElementValue($crawler, self::CONDITION_KEY_XPATH);
            $conditionCode = str_replace('calculator_answers[', '', $conditionCode);
            $conditionCode = str_replace(']', '', $conditionCode);

            $conditionValue = null;

            $conditionConf = $this->options->filter(function ($option) {
                return $option->element == 'OPTION_VALUE';
            })->first();

            $conditionValueXpath = null;

            $poorConditionCode = null;
            $poorConditionValue = null;

            switch ($conditionConf->value) {
                case 'broken_power_on':
                    $conditionValueXpath = '//li[@id="poor"]/@data-option';

                    $poorConditionCodeXpath = '//*[@data-api-name="yes"]/@name';
                    $poorConditionCode = $this->__getElementValue($crawler, $poorConditionCodeXpath);
                    $poorConditionCode = str_replace('calculator_answers[', '', $poorConditionCode);
                    $poorConditionCode = str_replace(']', '', $poorConditionCode);

                    $poorConditionValueXpath = '//*[@data-api-name="yes"]/@value';
                    $poorConditionValue = $this->__getElementValue($crawler, $poorConditionValueXpath);

                    break;
                case 'broken_power_off':
                    $conditionValueXpath = '//li[@id="poor"]/@data-option';

                    $poorConditionCodeXpath = '//*[@data-api-name="no"]/@name';
                    $poorConditionCode = $this->__getElementValue($crawler, $poorConditionCodeXpath);
                    $poorConditionCode = str_replace('calculator_answers[', '', $poorConditionCode);
                    $poorConditionCode = str_replace(']', '', $poorConditionCode);

                    $poorConditionValueXpath = '//*[@data-api-name="no"]/@value';
                    $poorConditionValue = $this->__getElementValue($crawler, $poorConditionValueXpath);

                    break;
                case 'good':
                    $conditionValueXpath = '//li[@id="good"]/@data-option';
                    $poorConditionCodeXpath = '//*[@data-api-name="yes"]/@name';
                    $poorConditionCode = $this->__getElementValue($crawler, $poorConditionCodeXpath);
                    $poorConditionCode = str_replace('calculator_answers[', '', $poorConditionCode);
                    $poorConditionCode = str_replace(']', '', $poorConditionCode);
                    $poorConditionValueXpath = '//*[@data-api-name="yes"]/@value';
                    $poorConditionValue = $this->__getElementValue($crawler, $poorConditionValueXpath);
                    break;
                case 'flawless':
                    $conditionValueXpath = '//li[@id="perfect"]/@data-option';
                    $poorConditionCodeXpath = '//*[@data-api-name="yes"]/@name';
                    $poorConditionCode = $this->__getElementValue($crawler, $poorConditionCodeXpath);
                    $poorConditionCode = str_replace('calculator_answers[', '', $poorConditionCode);
                    $poorConditionCode = str_replace(']', '', $poorConditionCode);
                    $poorConditionValueXpath = '//*[@data-api-name="yes"]/@value';
                    $poorConditionValue = $this->__getElementValue($crawler, $poorConditionValueXpath);
                    break;
            }

            if (!is_null($conditionValueXpath)) {
                $conditionValue = $this->__getElementValue($crawler, $conditionValueXpath);
            }

            $dataContentNodes = $crawler->filterXPath(self::DATA_CONTENT_XPATH);

            $dataContents = [];

            $dataContentNodes->each(function (Crawler $dataContentNode) use (&$dataContents) {
                $name = $dataContentNode->attr('name');
                $value = $dataContentNode->attr('value');
                $dataContents[$name] = $value;
            });


            $requestData = [
                'utf8 ' => $this->utf8,
                'authenticity_token' => $this->authenticity_token,
                'product_id' => $this->product_id,
                'email ' => $this->email,
                'sell_another_device' => $this->sell_another_device,
                'imei_input_field' => $this->imei_input_field,
                'timestamp' => time(),
                'promo' => $this->promo,
                'alternate_value' => $this->alternate_value,
                'n ' => $this->n,
            ];
            $requestData["calculator_answers[{$conditionCode}]"] = $conditionValue;
            if (!is_null($poorConditionCode) && !is_null($poorConditionValue)) {
                $requestData["calculator_answers[{$poorConditionCode}]"] = $poorConditionValue;
            }

            $requestData = array_merge($dataContents, $requestData);
            $url = self::API_URL . $this->product_id . "/calculation.json?";
            $url = $url . http_build_query($requestData);
            $response = Curl::to($url)
                ->withHeaders($this->headers)
                ->returnResponseObject()
                ->asJsonResponse()
                ->withOption("FOLLOWLOCATION", true)
                ->get();

            if ($response->status === 200) {
                $this->productInfo = $response->content;
            }
        }
    }

    private function __getElementValue(Crawler $crawler, $xpath)
    {
        $xpathNodes = $crawler->filterXPath($xpath);

        foreach ($xpathNodes as $xpathNode) {
            if ($xpathNode->nodeValue) {
                $extraction = $xpathNode->nodeValue;
            } else {
                $extraction = $xpathNode->textContent;
            }
            return $extraction;
        }
    }
}