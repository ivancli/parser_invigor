<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 14/08/2017
 * Time: 2:17 PM
 */

namespace IvanCLI\Parser\Repositories\ITSWORTHMORE;


use IvanCLI\Parser\Contracts\ParserContract;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

    protected $products = [];
    protected $attributes = [];

    protected $entryId;

    const API_URL = "https://www.itsworthmore.com/ajax/get-product-price";

    protected $headers = [
        'Accept-Language: en-us',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36',
        'Connection: Keep-Alive',
        'Cache-Control: no-cache',
        'Accept: application/json',
        'Content-Type:application/x-www-form-urlencoded; charset=UTF-8'
    ];

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
        $this->__getEntryID();

        $paramConfs = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        });

        $params = [];

        foreach ($paramConfs as $paramConf) {
            list($element, $value) = explode(':', $paramConf->value);
            $element = preg_replace('#\]\[(.*?)\]#', '][]', $element);
            $params[$element] = intval($value);
        }
        $params['entry_id'] = intval($this->entryId);

        $response = Curl::to('https://www.itsworthmore.com')
            ->returnResponseObject()
            ->withHeaders($this->headers)
            ->withOption("FOLLOWLOCATION", true)
            ->withOption("HEADER", true)
            ->get();


        if (is_object($response)) {

            preg_match_all('/Set-Cookie:(.*?);/', $response->content, $m);
            if (isset($m[1])) {
                $postData = "";
                $cookies = $m[1];
                foreach ($cookies as $cookie) {
                    list($index, $value) = (explode('=', $cookie, 2));
                    $postData .= "$index=$value;";
                }
                $this->headers[] = 'Cookie:' . $postData;
                $response = Curl::to(self::API_URL)
                    ->returnResponseObject()
                    ->withHeaders($this->headers)
                    ->withOption("FOLLOWLOCATION", true)
                    ->withData($params)
                    ->post();

                dd($response);
            }
        }

        if ($response->status === 200) {
            $productInfo = json_decode($response->content);
            if (!is_null($productInfo) && json_last_error() === JSON_ERROR_NONE) {

                $arrayConf = $this->options->filter(function ($option) {
                    return $option->element == 'ARRAY';
                })->first();

                /*check array configuration to locate property in $item */
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

    public function __getEntryID()
    {
        if (!is_null($this->content)) {
            $crawler = new Crawler($this->content);
            $entryIdNodes = $crawler->filterXPath('//input[@name="entry_id"]');
            if ($entryIdNodes->count() > 0) {
                $this->entryId = $entryIdNodes->first()->attr('value');
            }
        }
    }
}