<?php

namespace Wikimart\UsefulTools\Helper;

/**
 * @author oleg.emelyanov
 */
class XPathHelper
{
    /**
     * @param $content
     *
     * @return \DOMXpath
     */
    public function getXPathFromContent($content)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        @$dom->loadHTML($content);
        return  new \DOMXpath($dom);
    }
}