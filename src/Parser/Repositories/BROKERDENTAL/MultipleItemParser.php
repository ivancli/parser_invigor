<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 14/07/2017
 * Time: 9:39 AM
 */

namespace IvanCLI\Parser\Repositories\BROKERDENTAL;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    const OPTION_XPATH = '//*[@id="super-product-table"]/tbody/tr';

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
        $optionIdMeta = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        if (!is_null($optionIdMeta)) {
            $optionId = $optionIdMeta->value;

            $crawler = new Crawler($this->content);
            $optionNodes = $crawler->filterXPath(self::OPTION_XPATH . '//*[@data-product-id="' . $optionId . '"]');
            if ($optionNodes->count() > 0) {

                $optionNode = $optionNodes->first();

                $xpathConfs = $this->options->filter(function ($option) {
                    return $option->element == 'XPATH';
                });
                $extractions = [];
                foreach ($xpathConfs as $xpathConf) {
                    $xpath = $xpathConf->value;
                    $resultNodes = $optionNode->filterXPath($xpath);

                    foreach ($resultNodes as $xpathNode) {
                        if ($xpathNode->nodeValue) {
                            $extraction = $xpathNode->nodeValue;
                        } else {
                            $extraction = $xpathNode->textContent;
                        }
                        $extractions[] = $extraction;
                    }
                }
                $this->extractions = $extractions;

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
}