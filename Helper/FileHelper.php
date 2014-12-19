<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

/**
 * @author igor.lobach, viktor.safronov
 */
class FileHelper
{

    /**
     * @var CmdHelper 
     */
    private $cmdHelper;
    /**
     * @var TmpHelper 
     */
    private $tmpHelper;
    /**
     * @var DirHelper
     */
    private $dirHelper;


    public function __construct(CmdHelper $cmdHelper = null, TmpHelper $tmpHelper = null, DirHelper $dirHelper = null)
    {
        $this->cmdHelper = is_null($cmdHelper) ? new CmdHelper() : $cmdHelper;
        $this->tmpHelper = is_null($tmpHelper) ? new TmpHelper() : $tmpHelper;
        $this->dirHelper = is_null($dirHelper) ? new DirHelper() : $dirHelper;
    }

    /**
     * Returns html headers array for downloading file via browser
     *
     * @param $fileContent
     * @param $filename
     * @param $contentType
     *
     * @return array
     */
    public function getHeadersForDownloadFile($fileContent, $filename, $contentType)
    {
        return array(
            'Content-Description'       => 'File Transfer',
            'Content-Type'              => $contentType,
            'Content-Disposition'       => 'attachment; filename="' . $filename . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires'                   => 0,
            'Cache-Control'             => 'must-revalidate',
            'Pragma'                    => 'public',
            'Content-Length'            => strlen($fileContent)
        );
    }

    /**
     * Splits a file into multiple files by $onePartLinesNum lines in each file
     *
     * @param $filePath
     * @param $onePartLinesNum
     *
     * @return array
     * @throws HelperException
     */
    public function splitFile($filePath, $onePartLinesNum)
    {
        $onePartLinesNum = intval($onePartLinesNum);
        if (!$onePartLinesNum) {
            throw new HelperException('param onePartLinesNum must be more then zero');
        }

        if (!is_readable($filePath)) {
            throw new HelperException('file ' . $filePath . ' is not exist or not readable');
        }

        $handle      = fopen($filePath, 'r');
        $lineCount   = 0;
        $part        = 1;
        $handlePart  = null;
        $resultPaths = array();
        while (!feof($handle)) {
            if (!$lineCount) {
                if ($handlePart) {
                    fclose($handlePart);
                }
                $handlePart    = fopen($resultPath = $filePath . '_' . $part, 'w');
                $resultPaths[] = $resultPath;
            }

            $line = fgets($handle);

            //vsafronov: skip empty line
            if (!trim($line)) {
                continue;
            }

            fwrite($handlePart, $line, strlen($line));
            $lineCount++;

            if ($lineCount == $onePartLinesNum) {
                $lineCount = 0;
                $part++;
            }
        }

        fclose($handlePart);
        fclose($handle);


        return $resultPaths;
    }

    /**
     * @param $filePath
     *
     * @return array
     */
    public function uncompressFile($filePath)
    {
        $type = mime_content_type($filePath);
        switch ($type) {
            case 'application/x-gzip':
                //vsafronov: if file is tgz (tar.gz)
                return $this->uncompressFile($this->unGzipFile($filePath));
            case 'application/zip':
                return $this->unzipFile($filePath);
            case 'application/x-tar':
                return $this->unTarFile($filePath);
            default:
                return array($filePath);
        }
    }

    /**
     * @param $filePath
     *
     * @return bool
     */
    public function isArchiveFile($filePath)
    {
        return in_array(mime_content_type($filePath), array('application/x-gzip', 'application/zip'));
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public function isFilePath($string)
    {
        return is_string($string) && substr($string, 0, 1) === '/' && file_exists($string);
    }

    /**
     * @param $filePath
     *
     * @return string
     * @throws HelperException
     */
    public function unGzipFile($filePath)
    {
        $tmpFilePath = $this->tmpHelper->getTmpFile('ungzip');
        $this->cmdHelper->execute('gzip -dc ' . $filePath . ' > ' . $tmpFilePath);

        return $tmpFilePath;
    }

    /**
     * @param $filePath
     *
     * @return array
     * @throws HelperException
     */
    public function unTarFile($filePath)
    {
        $tmpDirPath = $this->tmpHelper->createUniqTmpDir('untar');
        $this->cmdHelper->execute('tar -xf ' . $filePath . ' -C ' . $tmpDirPath);

        return $this->dirHelper->getAllFilesFromDir($tmpDirPath);
    }

    /**
     * @param $filePath
     *
     * @return array
     */
    public function unZipFile($filePath)
    {
        $tmpDirPath = $this->tmpHelper->createUniqTmpDir('unzip');
        $this->cmdHelper->execute('unzip ' . $filePath . ' -d ' . $tmpDirPath);

        return $this->dirHelper->getAllFilesFromDir($tmpDirPath);
    }

    /**
     * @param $filePath
     *
     * @return array
     */
    public function zipFile($filePath)
    {
        $zipFilePath = $filePath . '.zip';
        $this->cmdHelper->execute('zip ' . $zipFilePath . ' ' . $filePath);

        return $zipFilePath;
    }
}
