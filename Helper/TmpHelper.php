<?php

namespace Wikimart\UsefulTools\Helper;

/**
 * @author viktor.safronov
 */
class TmpHelper
{

    /**
     * @var string $spe
     */
    private $tmpDir;

    private $subDirToTmpPath;

    public function __construct($specificDir = null)
    {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            (is_null($specificDir) ? '' : trim(trim($specificDir, DIRECTORY_SEPARATOR)) . DIRECTORY_SEPARATOR);

        if (!is_dir($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
    }

    /**
     * @param string|null $subDir
     *
     * @return string
     */
    public function getTmpDir($subDir = null)
    {
        if (!empty($this->subDirToTmpPath[$subDir])) {
            return $this->subDirToTmpPath[$subDir];
        }

        $returnDir = $this->tmpDir;

        if ($subDir) {
            $returnDir .= $subDir . DIRECTORY_SEPARATOR;
        }

        if (!is_dir($returnDir)) {
            mkdir($returnDir, 0777, true);
        }

        return $this->subDirToTmpPath[$subDir] = $returnDir;
    }

    /**
     * @param string      $prefix
     * @param string|null $subDir
     *
     * @return string
     */
    public function getTmpFile($prefix, $subDir = null)
    {
        return tempnam($this->getTmpDir($subDir), $prefix . '_');
    }

    /**
     * @param $prefix
     *
     * @return string
     */
    public function createUniqTmpDir($prefix)
    {
        $filePath = $this->getTmpFile($prefix);
        unlink($filePath);
        $dirPath = $filePath . DIRECTORY_SEPARATOR;
        mkdir($dirPath);

        return $dirPath;
    }
}