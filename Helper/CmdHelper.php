<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author viktor.safronov
 */
class CmdHelper
{
    public function execute($cmd)
    {
        exec($cmd . ' 2>&1', $output, $status);
        $output = implode("\n", $output);

        if ($status !== 0) {
            throw new HelperException(
                'Could not execute cmd: "' . $cmd . '". ' .
                'Status: "' . $status . '". ' .
                'Output: "' . $output . '"');
        }

        return $output;
    }

    /**
     * @param $processName
     *
     * @return string
     */
    public function getProcessNum($processName)
    {
        return exec('ps aux | grep ' . $processName . ' | grep -v "grep ' . $processName . '" | grep -v "/bin/sh -c" | wc -l');
    }
}