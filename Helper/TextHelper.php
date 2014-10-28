<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\HelperException;

/**
 * @author oleg.emelyanov, viktor.safronov
 */
class TextHelper
{
    /**
     * @param  string $string string to convert to UTF-8
     * @param  string $from   current encoding
     * @param  bool   $ignore adding //IGNORE if true
     *
     * @throws HelperException
     * @return string UTF-8 encoded string
     */
    public function convertToUtf8($string, $from = 'cp1251', $ignore = false)
    {
        if (!$this->isUTF8($string)) {
            return $this->I18N_toUTF8($string, $from, $ignore);
        }

        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function renameYoToE($string)
    {
        return str_replace(array('ё', 'Ё'), array('е', 'Е'), $string);
    }

    /**
     * @param $str
     *
     * @return bool
     */
    public function isKanji($str)
    {
        return preg_match('/[\x{4E00}-\x{9FBF}]/u', $str);
    }

    /**
     * @param $str
     *
     * @return bool
     */
    public function isHiragana($str)
    {
        return preg_match('/[\x{3040}-\x{309F}]/u', $str);
    }

    /**
     * @param $str
     *
     * @return bool
     */
    public function isKatakana($str)
    {
        return preg_match('/[\x{30A0}-\x{30FF}]/u', $str);
    }


    /**
     * @param $str
     *
     * @return bool
     */
    public function isJapanese($str)
    {
        return $this->isKanji($str) || $this->isHiragana($str) || $this->isKatakana($str);
    }

    /**
     * @param $str
     *
     * @return int
     */
    public function isRussian($str)
    {
        return preg_match('/[а-яё]/iu', $str);
    }

    /**
     * @param $str
     *
     * @return int
     */
    public function isLatin($str)
    {
        return preg_match('/[a-z\x{00DF}\x{00E4}\x{00C4}\x{00F6}\x{00D6}\x{00FC}\x{00DC}.]+/iu', $str);
    }

    /**
     * @param $str
     *
     * @return string
     */
    public function deleteUtf8BrokenSymbols($str)
    {
        return iconv('utf-8', 'utf-8//IGNORE', $str);
    }

    /**
     * Checks if a string is an utf8.
     *
     * Yi Stone Li<yili@yahoo-inc.com>
     * Copyright (c) 2007 Yahoo! Inc. All rights reserved.
     * Licensed under the BSD open source license
     *
     * @param string
     *
     * @return bool true if $string is valid UTF-8 and false otherwise.
     */
    public function isUTF8($string)
    {
        for ($idx = 0, $strlen = strlen($string); $idx < $strlen; $idx++) {
            $byte = ord($string[$idx]);

            if ($byte & 0x80) {
                if (($byte & 0xE0) == 0xC0) {
                    // 2 byte char
                    $bytes_remaining = 1;
                } else if (($byte & 0xF0) == 0xE0) {
                    // 3 byte char
                    $bytes_remaining = 2;
                } else if (($byte & 0xF8) == 0xF0) {
                    // 4 byte char
                    $bytes_remaining = 3;
                } else {
                    return false;
                }

                if ($idx + $bytes_remaining >= $strlen) {
                    return false;
                }

                while ($bytes_remaining--) {
                    if ((ord($string[++$idx]) & 0xC0) != 0x80) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Converts strings to UTF-8 via iconv.
     *
     * This file comes from Prado (BSD License)
     *
     * @param  string $string string to convert to UTF-8
     * @param  string $from   current encoding
     * @param  bool   $ignore adding //IGNORE if true
     *
     * @throws HelperException
     * @return string UTF-8 encoded string
     */
    public function I18N_toUTF8($string, $from, $ignore = false)
    {
        $from = strtoupper($from);
        if ($from != 'UTF-8') {
            $s = iconv($from, ($ignore ? 'UTF-8//IGNORE' : 'UTF-8'), $string);  // to UTF-8

            if ($s === false) {
                throw new HelperException("iconv can not convert string", array($string));
            }

            return $s;
        }

        return $string;
    }
}