<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author igor.lobach, viktor.safronov
 */
class UrlHelper
{
    private $ruToSpecArray = array(
        " " => "%20",
        "а" => "%D0%B0", "А" => "%D0%90", "б" => "%D0%B1", "Б" => "%D0%91", "в" => "%D0%B2", "В" => "%D0%92",
        "г" => "%D0%B3", "Г" => "%D0%93", "д" => "%D0%B4", "Д" => "%D0%94", "е" => "%D0%B5", "Е" => "%D0%95",
        "ё" => "%D1%91", "Ё" => "%D0%81", "ж" => "%D0%B6", "Ж" => "%D0%96", "з" => "%D0%B7", "З" => "%D0%97",
        "и" => "%D0%B8", "И" => "%D0%98", "й" => "%D0%B9", "Й" => "%D0%99", "к" => "%D0%BA", "К" => "%D0%9A",
        "л" => "%D0%BB", "Л" => "%D0%9B", "м" => "%D0%BC", "М" => "%D0%9C", "н" => "%D0%BD", "Н" => "%D0%9D",
        "о" => "%D0%BE", "О" => "%D0%9E", "п" => "%D0%BF", "П" => "%D0%9F", "р" => "%D1%80", "Р" => "%D0%A0",
        "с" => "%D1%81", "С" => "%D0%A1", "т" => "%D1%82", "Т" => "%D0%A2", "у" => "%D1%83", "У" => "%D0%A3",
        "ф" => "%D1%84", "Ф" => "%D0%A4", "х" => "%D1%85", "Х" => "%D0%A5", "ц" => "%D1%86", "Ц" => "%D0%A6",
        "ч" => "%D1%87", "Ч" => "%D0%A7", "ш" => "%D1%88", "Ш" => "%D0%A8", "щ" => "%D1%89", "Щ" => "%D0%A9",
        "ъ" => "%D1%8A", "Ъ" => "%D0%AA", "ы" => "%D1%8B", "Ы" => "%D0%AB", "ь" => "%D1%8C", "Ь" => "%D0%AC",
        "э" => "%D1%8D", "Э" => "%D0%AD", "ю" => "%D1%8E", "Ю" => "%D0%AE", "я" => "%D1%8F", "Я" => "%D0%AF"
    );

    private $specToRuArray;


    /** @var  TextHelper */
    private $textHelper;

    public function __construct(TextHelper $textHelper)
    {
        $this->textHelper = is_null($textHelper) ? new TextHelper() : $textHelper;
    }


    /**
     * @param $url
     *
     * @return string
     */
    public function encodeRu($url)
    {
        return strtr($url, $this->ruToSpecArray);
    }

    /**
     * @return mixed
     */
    private function getSpecToRuArray()
    {
        if (!$this->specToRuArray) {
            $this->specToRuArray = array_flip($this->ruToSpecArray);
            foreach ($this->specToRuArray as $spec => $ru) {
                $this->specToRuArray[strtolower($spec)] = $ru;
            }
        }

        return $this->specToRuArray;
    }

    /**
     * @param $url
     *
     * @return string
     */
    public function decodeRu($url)
    {
        return strtr($url, $this->getSpecToRuArray());
    }


    /**
     * @param string $url
     * @param bool   $filterQuery
     * @return string
     */
    public function normalizeUrl($url, $filterQuery = false)
    {
        $originalUrl = $url;

        // psaharov: without http parse_url() incorrect define host
        $url = $this->addHttpIfNeeded($url);

        $urlParams = parse_url($url);

        if (!isset($urlParams['host'])) {
            return $originalUrl;
        }

        $uri  = isset($urlParams['path']) ? $urlParams['path'] : '';

        $host = trim($this->clearHost($urlParams['host']));
        $uri  = trim($this->urldecodeFully($uri));

        $uri = !empty($uri) ? preg_replace('/\/+/', '/', $uri) : '';

        $query = '';
        if (array_key_exists('query', $urlParams) && $filterQuery) {
            $query .= '?' . $urlParams['query'];
        }

        return $host.$uri.$query;
    }

    private function urldecodeFully($string)
    {
        $i = 0;
        do {
            $originalString = $string;
            $string = $this->textHelper->convertToUtf8(urldecode($string));
            $i++;
            if ($i > 100) {
                throw new HelperException('Can not decode url');
            }
        } while ($string !== $originalString);

        return $string;
    }

    /**
     * @param $urls
     *
     * @return array
     */
    public function normalizeUrls($urls)
    {
        $resultUrls = array();
        foreach ($urls as $url) {
            $resultUrls[] = $this->normalizeUrl($url);
        }

        return $resultUrls;
    }


    /**
     * @param      $url
     * @param bool $secure
     *
     * @return string
     */
    public function addHttpIfNeeded($url, $secure = false)
    {
        if (preg_match('@^(http(s)?|ftp)\:\/\/@i', $url)) {
            return $url;
        }

        return 'http' . ($secure ? 's' : '') . '://' . $url;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function delHttpIfNeeded($url)
    {
        $host = parse_url($this->addHttpIfNeeded($url), PHP_URL_HOST);
        if (preg_match('/^www\.[^\.]+$/ui', $host)) {
            return $url;
        }

        return preg_replace('@^http(s)?\:\/\/@i', '', $url);
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function delWwwIfNeeded($url)
    {
        return preg_replace('@^(http(s)?\:\/\/)?www\.@i', '$1', $url);
    }

    /**
     * @param        $getParams
     * @param string $errorMsg
     *
     * @return array
     * @throws HelperException
     */
    public function checkGetParams($getParams, $errorMsg = 'Get params are incorrect')
    {
        $getParams = $getParams ? array_map('trim', explode(',', $getParams)) : array();

        foreach ($getParams as $key => $getParam) {
            $data = array_map('trim', explode('=', $getParam));
            if (count($data) !== 2) {
                throw new HelperException($errorMsg);
            }

            unset($getParams[$key]);
            $getParams[$key] = $data[0] . '=' . $data[1];
        }

        return $getParams;
    }

    /**
     * @param $url
     *
     * @return mixed|string
     */
    public function delHttpAndWwwIfNeeded($url)
    {
        $url = trim($url);
        $url = $this->delHttpIfNeeded($url);
        $url = $this->delWwwIfNeeded($url);

        return $url;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function getHostFromUrl($url)
    {
        $url  = $this->addHttpIfNeeded($url);
        $host = parse_url($url, PHP_URL_HOST);

        return $this->clearHost($host);
    }

    /**
     * @param $host
     *
     * @return mixed
     */
    public function clearHost($host)
    {
        $host = $this->delWwwIfNeeded($host);

        //vsafronov: convert xn-- format to russian domain
        $host = idn_to_utf8($host);
        //vsafronov: google return url http://sovhoz%20imeni%20lenina.simbis.su/catalog/product/5259/plavki-dlya-grudnichkov-pdp-010/
        // it equal http://sovhoz-imeni-lenina.simbis.su/catalog/product/5259/plavki-dlya-grudnichkov-pdp-010/
        $host = str_replace('%20', '-', $host);

        //vsafronov: google return url http://nazran%27.simbis.su/catalog/product/5259/plavki-dlya-grudnichkov-pdp-002/
        // it equal http://nazran.simbis.su/catalog/product/5259/plavki-dlya-grudnichkov-pdp-002/
        $host = str_replace('%27', '', $host);
        $host = str_replace('\'', '', $host);

        return $host;
    }

    /**
     * @param $url
     *
     * @return string
     */
    public function addSlashIfNeeded($url)
    {
        if (mb_substr($url, -1) != '/') {
            $url = $url . '/';
        }

        return $url;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function delSchemeIfNeeded($url)
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme) {
            return str_replace($scheme . '://', '', $url);
        }

        return $url;
    }


//    /**
//     * @param $url
//     *
//     * @return mixed
//     * @throws HelperException
//     */
//
//    public function prepareUrl($url)
//    {
//        $preparedUrl = null;
//        $i = 0;
//        do {
//            if ($preparedUrl) {
//                $url = $preparedUrl;
//            }
//
//            $preparedUrl = $this->textHelper->convertToUtf8(urldecode($url));
//            $i++;
//
//            if ($i > 100) {
//                throw new HelperException('Can not decode url');
//            }
//
//        } while ($preparedUrl != $url);
//
//        return $this->delHttpIfNeeded(trim($preparedUrl));
//    }


    /**
     * @param array $urls
     * @param null $invalidUrl
     * @throws HelperException
     */
    public function validateUrls(array $urls, &$invalidUrl = null)
    {
        foreach ($urls as $url) {
            if (!$this->isUrlValid($url)) {
                $invalidUrl = $url;
                throw new HelperException('Invalid url found: "' . $url . '"');
            }
        }
    }

    /**
     * @param array $urls
     * @param array $invalidUrls
     * @return array
     */
    public function getValidUrls(array $urls, &$invalidUrls = null)
    {
        $validUrls = array();
        $invalidUrls = array();
        foreach ($urls as $url) {
            if (!$this->isUrlValid($url)) {
                $invalidUrls[] = $url;
                continue;
            }
            $validUrls[] = $url;
        }
        return $validUrls;
    }

    public function areUrlsValid(array $urls, &$invalidUrl = null)
    {
        try {
            $this->validateUrls($urls, $invalidUrl);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateUrl($url)
    {
        $this->validateUrls(array($url));
    }

    /**
     * @param $url
     * @return bool
     */
    public function isUrlValid($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (strpos($host, '.') === false) {
            return false;
        }

        return true;
    }

    public function filterUrls(array $urls)
    {
        return array_slice(array_filter($urls, array(__CLASS__, 'isUrlValid')), 0);
    }
}
