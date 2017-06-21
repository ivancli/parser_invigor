<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 21/06/2017
 * Time: 2:31 PM
 */

namespace IvanCLI\Parser\Repositories\LIGHTS2YOU;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

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
        $this->extractions = $this->__extract();
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract()
    {
        preg_match('#"productPrice":(.*?),#', $this->content, $matches);

        $extractions = [];

        if (isset($matches[1])) {
            $basePrice = floatval($matches[1]);

            $crawler = new Crawler($this->content);

            $optionConfs = $this->options->filter(function ($option) {
                return $option->element == 'OPTION_VALUE';
            });

            $additionalCosts = 0;


            foreach ($optionConfs as $optionConf) {
                $optionNodes = $crawler->filterXPath('//option[@value="' . $optionConf->value . '"]');
                if ($optionNodes->count() > 0) {
                    $optionNode = $optionNodes->first();
                    $additionalCosts += floatval($optionNode->attr("price"));
                }
            }
            $total = $basePrice + $additionalCosts;
            $extractions[] = $total;
        }

        return $extractions;
    }
}