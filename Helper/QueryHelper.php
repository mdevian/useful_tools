<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class QueryHelper
{
    public function validateQueries(array $queries, &$invalidQuery = null)
    {
        foreach ($queries as $query) {
            if (!$this->isQueryValid($query)) {
                $invalidQuery = $query;
                throw new HelperException('Invalid query found: "' . $query . '"');
            }
        }
    }

    public function areQueriesValid(array $queries, &$invalidQuery = null)
    {
        try {
            $this->validateQueries($queries, $invalidQuery);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateQuery($query)
    {
        $this->validateQueries(array($query));
    }

    public function isQueryValid($query)
    {
        if (trim($query) === '') {
            return false;
        }

        $regExp = '/^[\p{L}\p{M}\p{N}\p{P}\p{S}\x20]+$/iu';
        if (!preg_match($regExp, $query)) {
            return false;
        }

        return true;
    }

    public function filterQueries(array $queries)
    {
        return array_slice(array_filter($queries, array(__CLASS__, 'isQueryValid')), 0);
    }
}
