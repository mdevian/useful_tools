<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\HelperException;

/**
 * @author igor.lobach
 */
class HtmlHelper
{
    /**
     * @param $html
     *
     * @return mixed
     * @throws HelperException
     */
    public function removeFirstTag($html)
    {
        preg_match('@^<[^>]+>(.*)</[^>]+>$@s', trim($html), $matches);
        if (!isset($matches[1])) {
            throw new HelperException('Dont find first tag');
        }

        return $matches[1];
    }
}


