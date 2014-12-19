<?php
/**
 * @author viktor.safronov
 */

namespace Wikimart\UsefulTools\Exception;

class HelperException extends \Exception
{
    private $data;

    /**
     * @param string $message
     * @param array $data
     */
    public function __construct($message, $data = array())
    {
        $this->data = $data;
        parent::__construct($message);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}