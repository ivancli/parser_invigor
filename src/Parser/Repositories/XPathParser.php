<?php
/**
 * Created by PhpStorm.
 * User: ivan.li
 * Date: 3/13/2017
 * Time: 4:27 PM
 */

namespace IvanCLI\Parser\Repositories;


use IvanCLI\Parser\Contracts\ParserContract;
use Symfony\Component\DomCrawler\Crawler;

class XPathParser implements ParserContract
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

        $xpath = $this->options['xpath'];
        if (isset($xpath) && !empty($xpath)) {
            if (is_array($xpath)) {
                foreach ($xpath as $xp) {
                    $this->extractions [] = $this->__extract($xp);
                }
            } else {
                $this->extractions [] = $this->__extract($xpath);
            }
        }
        return null;
    }

    public function getExtractions()
    {
        return $this->extractions;
    }

    private function __extract($xpath)
    {
        $crawler = new Crawler($this->content);
        $xpathNodes = $crawler->filterXPath($xpath);
        $extractions = [];
        foreach ($xpathNodes as $xpathNode) {
            if (trim($xpathNode->nodeValue)) {
                $extraction = $xpathNode->nodeValue;
            } else {
                $extraction = $xpathNode->textContent;
            }
            $extractions[] = $extraction;
        }
        return $extractions;
    }
}