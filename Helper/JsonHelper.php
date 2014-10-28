<?php

namespace Wikimart\UsefulTools\Helper;

/**
 * @author viktor.safronov
 */
class JsonHelper
{
    /**
     * @param      $data
     * @param bool $normalizeHtmlTags
     * @param bool $replaceEnclosureSlashes
     *
     * @return string
     */
    function economyEncode($data, $normalizeHtmlTags = true, $replaceEnclosureSlashes = false)
    {
        $result = preg_replace_callback(
            '/\\\\u([0-9a-f]{4})/i',
            function ($val) {
                return mb_decode_numericentity('&#' . intval($val[1], 16) . ';', array(0, 0xffff, 0, 0xffff), 'utf-8');
            },
            json_encode($data)
        );

        if ($normalizeHtmlTags) {
            $result = preg_replace('@\<\\\/([a-z]+)\>@i', '</$1>', $result);
        }

        if ($replaceEnclosureSlashes) {
            $result = preg_replace("/\\\\(.)/", "$1", $result);
        }
        return $result;
    }
}