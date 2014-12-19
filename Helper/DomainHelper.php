<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class DomainHelper
{
    /**
     * @var UrlHelper
     */
    private $urlHelper;
    /**
     * @var TextHelper
     */
    private $textHelper;


    public function __construct(UrlHelper $urlHelper = null, TextHelper $textHelper = null)
    {
        $this->textHelper = is_null($textHelper) ? new TextHelper() : $textHelper;
        $this->urlHelper  = is_null($urlHelper) ? new UrlHelper($textHelper) : $urlHelper;
    }


    public function validateDomain($domain)
    {
        $this->validateDomains(array($domain));
    }

    public function validateDomains(array $domains, &$invalidDomain = null)
    {
        foreach ($domains as $domain) {
            if (!$this->isDomainValid($domain)) {
                $invalidDomain = $domain;
                throw new HelperException('Invalid domain found: "' . $domain . '"');
            }
        }
    }

    public function isDomainValid($domain)
    {
        //vsafronov: HACK: domain with symbol "_" is incorrect, but such hosts exist in the internet
        $domain = str_replace('_', '-', $domain);

        $url = $this->urlHelper->addHttpIfNeeded($domain);
        $result = $this->urlHelper->isUrlValid($url);

        //vsafronov: checking for cyrillic domains
        if (!$result) {
            if (preg_match('/\s/u', parse_url($url, PHP_URL_HOST))) {
                return false;
            }

            $unknownSymbols = preg_replace('/^[0-9a-z\-.]+$/iu', '', $domain);
            if ($this->textHelper->isRussian($unknownSymbols) or
                $this->textHelper->isJapanese($unknownSymbols) or
                $this->textHelper->isLatin($unknownSymbols)
            ) {
                return true;
            }
        }
        return $result;

    }


    public function filterDomains(array $domains)
    {
        return array_slice(array_filter($domains, array(__CLASS__, 'isDomainValid')), 0);
    }


    public function areDomainsValid(array $domains, &$invalidDomain = null)
    {
        try {
            $this->validateDomains($domains, $invalidDomain);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
