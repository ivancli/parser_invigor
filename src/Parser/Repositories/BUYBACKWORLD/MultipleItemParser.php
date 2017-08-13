<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 14/08/2017
 * Time: 9:39 AM
 */

namespace IvanCLI\Parser\Repositories\BUYBACKWORLD;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class MultipleItemParser implements ParserContract
{
    protected $content;
    protected $options;
    protected $extractions = [];

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
        $idConf = $this->options->filter(function ($option) {
            return $option->element == 'OPTION_VALUE';
        })->first();

        $id = $idConf->value;

        $xpath = '//*[@class="condition" and @id="' . $id . '"]';
        $extractions = [];
        if (!is_null($this->content)) {
            $xpathConf = $this->options->filter(function ($option) {
                return $option->element == 'XPATH';
            })->first();
            if (!is_null($xpathConf)) {
                $crawler = new Crawler($this->content);
                $filteredNodes = $crawler->filterXPath($xpath);
                $filteredNodes->each(function (Crawler $filteredNode) use ($xpathConf, &$extractions) {
                    $filteredTargets = $filteredNode->filterXPath($xpathConf->value);
                    foreach ($filteredTargets as $filteredTarget) {
                        if ($filteredTarget->nodeValue) {
                            $extraction = $filteredTarget->nodeValue;
                        } else {
                            $extraction = $filteredTarget->textContent;
                        }
                        $extractions[] = $extraction;
                    }
                });
            }
        }
        $this->extractions = $extractions;
        return $extractions;
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