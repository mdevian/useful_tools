<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author viktor.safronov
 */
class DateHelper
{
    /**
     * @param        $date
     * @param string $name
     *
     * @return bool
     * @throws HelperException
     */
    public function checkDate($date, $name = 'Date')
    {
        if (!$date) {
            throw new HelperException($name . ' is empty');
        }

        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $date)) {
            throw new HelperException($name . ' is incorrect, Must be format YYYY-MM-DD');
        }

        if ($date && !strtotime($date)) {
            throw new HelperException($name . ' does not exist');
        }

        return true;
    }

    /**
     * @return bool|string
     */
    public function getCurrentDate()
    {
        return $this->getDateFromTime(time());
    }

    /**
     * @param $time
     *
     * @return bool|string
     */
    public function getDateFromTime($time)
    {
        return date('Y-m-d', $time);
    }

    public function getCurrentTime()
    {
        return date('Y-m-d H:i:s');
    }
}