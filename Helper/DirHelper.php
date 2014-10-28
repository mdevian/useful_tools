<?php

namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\HelperException;

/**
 * @author viktor.safronov
 */
class DirHelper
{
    /**
     * scan directory for files and subdirectories excepts . and ..
     * returns array of subdirs and files
     *
     * @author evgeniy.terentev
     *
     * @param       $dir
     * @param array $addExclude - additional special exclusions for files or folders
     *
     * @return array
     */
    public function scandir($dir, $addExclude = array())
    {
        $exceptDirs = array('.', '..');
        $currentDir = scandir($dir);
        $result     = array_diff($currentDir, $exceptDirs, $addExclude);

        return $result;
    }

    public function getAllFilesFromDir($dirPath)
    {
        $dirPath = $this->addDirSeparatorToEnd($dirPath);
        $files   = array();
        foreach ($this->scandir($dirPath) as $file) {
            $filePath = $dirPath . $file;
            if (is_dir($filePath)) {
                $files = array_merge($files, $this->getAllFilesFromDir($filePath));
            } else {
                $files[] = $filePath;
            }
        }

        return $files;
    }

    /**
     * @param $dirPath
     *
     * @return string
     */
    public function addDirSeparatorToEnd($dirPath)
    {
        $lastSymbol = substr($dirPath, -1, 1);

        return $lastSymbol == DIRECTORY_SEPARATOR ? $dirPath : $dirPath . DIRECTORY_SEPARATOR;
    }


    /**
     * @author viktor.safronov
     *
     * @param     $hash
     * @param int $level
     * @param int $step
     *
     * @return string
     */
    public function generateSubDirs($hash, $level = 2, $step = 2)
    {
        $result = '';
        for ($i = 0; $i < $level; $i++) {
            $result .= substr($hash, $i * $step, $step) . DIRECTORY_SEPARATOR;
        }

        return $result . $hash;
    }

    /**
     * @param $filePath
     *
     * @return string
     */
    public function getPathWithoutFile($filePath)
    {
        return substr($filePath, 0, strrpos($filePath, DIRECTORY_SEPARATOR) + 1);
    }

    /**
     * @param $dir
     *
     * @return bool
     * @throws HelperException
     */
    public function createDirectoryIfNotExists($dir)
    {
        if (is_dir($dir)) {
            return true;
        }

        if (file_exists($dir)) {
            throw new HelperException('File with same name already exists');
        }

        mkdir($dir);

        return true;
    }
}
