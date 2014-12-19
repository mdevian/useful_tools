<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author igor.lobach, viktor.safronov, oleg.emelyanov
 */
class CsvHelper
{

    /**
     * @var TextHelper
     */
    private $textHelper;
    /**
     * @var TmpHelper
     */
    private $tmpHelper;


    public function __construct(TextHelper $textHelper = null, TmpHelper $tmpHelper = null)
    {
        $this->textHelper = is_null($textHelper) ? new TextHelper() : $textHelper;
        $this->tmpHelper  = is_null($tmpHelper) ? new TmpHelper() : $tmpHelper;
    }

    /**
     * @return string
     */
    private function getBom()
    {
        return chr(239) . chr(187) . chr(191);
    }

    /**
     * @param      $filePath
     * @param bool $byTitles
     * @param bool $strtolowerTitles
     * @param null $delimiter
     *
     * @return array
     * @throws HelperException
     */
    public function getArrayFromCsvFile(
        $filePath,
        $byTitles = false,
        $strtolowerTitles = false,
        $delimiter = null
    )
    {
        $lines = array();
        $func  = function ($line) use (&$lines) {
            $lines[] = $line;
        };
        $this->readFileLineByLine($func, $filePath, $byTitles, $strtolowerTitles, $delimiter);

        return $lines;
    }

    /**
     * @param      $array
     * @param      $filePath
     * @param bool $putTitlesFromKeys
     *
     * @return bool
     */
    public function saveCsvFileFromArray($array, $filePath, $putTitlesFromKeys = false)
    {
        $fp = fopen($filePath, 'w+');
        fwrite($fp, $this->getBom());
        if ($putTitlesFromKeys) {
            array_unshift($array, array_keys(reset($array)));
        }
        foreach ($array as $line) {
            fputcsv($fp, (array) $line, ';', '"');
        }

        return fclose($fp);
    }

    /**
     * @param array $array
     * @param bool  $putTitlesFromKeys
     *
     * @return bool|string
     */
    public function getCsvContentFromArray(array $array, $putTitlesFromKeys = false)
    {
        $tmpFile = $this->tmpHelper->getTmpFile('csv');
        $this->saveCsvFileFromArray($array, $tmpFile, $putTitlesFromKeys);
        $csvContent = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $csvContent;
    }

    /**
     * @param      $csvContent
     * @param bool $byTitles
     * @param bool $strtolowerTitles
     *
     * @return bool|string
     */
    public function getArrayFromCsvContent($csvContent, $byTitles = false, $strtolowerTitles = false)
    {
        $tmpFile = $this->tmpHelper->getTmpFile('csv');
        file_put_contents($tmpFile, $csvContent);
        $array = $this->getArrayFromCsvFile($tmpFile, $byTitles, $strtolowerTitles);
        unlink($tmpFile);

        return $array;
    }

    /**
     * @param        $callback
     * @param        $filePath
     * @param bool   $byTitles
     * @param bool   $strtolowerTitles
     * @param null   $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param string $possibleFileCharset
     *
     * @throws HelperException
     */
    public function readFileLineByLine(
        $callback,
        $filePath,
        $byTitles = false,
        $strtolowerTitles = false,
        $delimiter = null,
        $enclosure = '"',
        $escape = '"',
        $possibleFileCharset = 'cp1251'
    )
    {
        if (!is_callable($callback)) {
            throw new HelperException(
                sprintf('"%s" is not a valid callable.', is_array($callback) ?
                        sprintf('%s:%s', is_object($callback[0]) ? get_class($callback[0]) : $callback[0], $callback[1]) :
                        (is_object($callback) ? sprintf('Object(%s)', get_class($callback)) : var_export($callback, true)))
            );
        }

        if (!($handle = fopen($filePath, 'r'))) {
            throw new HelperException('Can not read file: ' . $filePath);
        }

        $titles = array();

        if (!$delimiter) {
            $delimiter = $this->detectDelimiter($handle);
        }

        if (!$delimiter) {
            $delimiter = ';';
        }

        $firstLine = true;
        while (($columns = $this->getColumnsByString($handle, $delimiter, $enclosure, $escape)) !== false) {
            //vsafronov: remove bom
            if ($firstLine) {
                $columns[0] = preg_replace('/^' . $this->getBom() . '/', '', $columns[0]);
            }

            //vsafronov: skip empty line
            if (count($columns) === 1 && trim(current($columns)) === '') {
                continue;
            }

            $columns = array_map(function ($item) use ($possibleFileCharset) {
                    return $this->textHelper->convertToUtf8(trim($item), $possibleFileCharset);
                }, $columns);

            if ($byTitles && !$titles) {
                $titles = $strtolowerTitles ? array_map('strtolower', $columns) : $columns;
                continue;
            }

            if ($byTitles) {
                if (count($titles) != count($columns)) {
                    throw new HelperException('Columns and titles should have an equal number of elements');
                }
                $line = array_combine($titles, $columns);
            } else {
                $line = $columns;
            }

            call_user_func($callback, $line);

            $firstLine = false;
        }

        fclose($handle);
    }

    /**
     * @param        $handle
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     *
     * @return array
     */
    private function getColumnsByString($handle, $delimiter = ';', $enclosure = '"', $escape = '"')
    {
        if ($enclosure) {
            return fgetcsv($handle, null, $delimiter, $enclosure, $escape);
        } else {
            $string = fgets($handle);

            return $string !== false ? explode($delimiter, $string) : false;
        }
    }

    /**
     * @param $handle
     *
     * @return int|null
     * @throws HelperException
     */
    private function detectDelimiter($handle)
    {
        do {
            $line = fgets($handle);
        } while (!trim($line));

        rewind($handle);

        $semicolon  = ';';
        $tabulation = "\t";

        $semicolonCount  = count(explode($semicolon, $line));
        $tabulationCount = count(explode($tabulation, $line));

        if ($semicolonCount == 1 && $tabulationCount == 1) {
            //vsafronov: file possible has one column
            return null;
        }

        if ($semicolonCount == $tabulationCount) {
            throw new HelperException(
                'Detect couple tabulation in csv file. Line: ' . $line
            );
        }

        return $semicolonCount > $tabulationCount ? $semicolon : $tabulation;
    }
}