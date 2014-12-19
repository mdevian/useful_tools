<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\Arrayable;
use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author viktor.safronov
 */
class ArrayHelper
{
    /**
     * @param      $array
     * @param      $keyName
     * @param null $valueName
     * @param bool $isMulti
     * @param null $multiKeyName
     *
     * @return array
     * @throws HelperException
     */
    public function toArrayByField($array, $keyName, $valueName = null, $isMulti = false, $multiKeyName = null)
    {
        $resultArray = array();

        foreach ($array as $item) {

            $value = isset($valueName) ? $item[$valueName] : $item;

            //todo: add hook
            //$key   = $item[$keyName];
            if (is_array($item)) {
                $key   = $item[$keyName];
            } else {
                $keymethod = 'get' . ucfirst($keyName);
                $key   = $item->$keymethod();
            }


            if ($isMulti) {
                if ($multiKeyName) {
                    $multiKey = $item[$multiKeyName];

                    if (isset($resultArray[$key][$multiKey])) {
                        throw new HelperException(
                            'Duplicate multi key "' . $multiKeyName . '": array[' . $key . '][' . $multiKey . ']',
                            array('key_value' => $key, 'multi_key_value' => $multiKey)
                        );
                    }

                    $resultArray[$key][$multiKey] = $value;
                    continue;
                }

                $resultArray[$key][] = $value;
                continue;
            }

            if (isset($resultArray[$key])) {
                throw new HelperException(
                    'Duplicate key "' . $keyName . '": array[' . $key . ']',
                    array('key_value' => $key)
                );
            }

            $resultArray[$key] = $value;
        }

        return $resultArray;
    }

    /**
     * @param $array
     *
     * @return array
     */
    public function mergeTwoLevel($array)
    {
        $resultArray = array();
        foreach ($array as $item) {
            $resultArray = array_merge($resultArray, $item);
        }

        return $resultArray;
    }

    /**
     * @param $array
     *
     * @return array
     * @throws HelperException
     */
    public function objectsToArray($array)
    {
        $resultArray = array();
        foreach ($array as $key => $item) {
            if (is_object($item)) {
                if (!($item instanceof Arrayable)) {
                    throw new HelperException('Returned object is not implements Arrayable interface');
                }
                $resultArray[$key] = $item->toArray();
            } elseif (is_array($item)) {
                $resultArray[$key] = $this->objectsToArray($item);
            } else {
                $resultArray[$key] = $item;
            }
        }

        return $resultArray;
    }

    /**
     * @param $array
     *
     * @return array
     */
    public function replaceNullToEmptyString($array)
    {
        $resultArray = array();
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                $resultArray[$key] = $this->replaceNullToEmptyString($item);
            } else {
                $resultArray[$key] = is_null($item) ? '' : $item;
            }
        }

        return $resultArray;
    }

    public function last (&$array, $key)
    {
        end($array);
        return $key === key($array);
    }
}