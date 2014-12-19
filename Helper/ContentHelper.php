<?php
namespace Wikimart\UsefulTools\Helper;

use Wikimart\UsefulTools\Exception\HelperException;

class ContentHelper
{
    /** @var  FileHelper */
    private $fileHelper;

    /** @var  TmpHelper */
    private $tmpHelper;

    /** @var  TextHelper */
    private $textHelper;

    public function __construct(
        FileHelper $fileHelper = null,
        TmpHelper $tmpHelper = null,
        TextHelper $textHelper = null
    ) {
        $this->tmpHelper =  is_null($tmpHelper)  ? new TmpHelper()  : $tmpHelper;
        $this->fileHelper = is_null($fileHelper) ? new FileHelper() : $fileHelper;
        $this->textHelper = is_null($textHelper) ? new TextHelper() : $textHelper;
    }

    public function getRowsFromFile($filePath, $filterEmpty = true, $unique = true, $lowercase = true, &$isUtf8 = null)
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new HelperException('File "' . $filePath . '" does not exist or is not readable');
        }

        return $this->getRowsFromContent(file_get_contents($filePath), $filterEmpty, $unique, $lowercase, $isUtf8);
    }

    public function getRowsFromContent(
        $content,
        $filterEmpty = true,
        $unique = true,
        $lowercase = true,
        &$isUtf8 = null
    ) {
        if (!($isUtf8 = $this->textHelper->isUTF8($content))) {
            $content = $this->convertContentFromCp1251ToUtf8($content);
        }

        if ($lowercase) {
            $content = mb_strtolower($content, 'utf-8');
        }

        $rows = explode("\n", $content);
        foreach ($rows as $rowKey => $row) {
            $rows[$rowKey] = $this->fixSpacesFromString($row);
        }

        if ($filterEmpty) {
            $rows = array_filter(
                $rows,
                function ($v) {
                    return $v !== '';
                }
            );
        }

        if ($unique) {
            $rows = array_unique($rows);
        }

        $rows = array_slice($rows, 0);
        return $rows;
    }

    public function fixSpacesFromString($string)
    {
        $replaceFrom = array('/[\x{00A0}\x{202F}]/u', '/[\x{2060}\x{FEFF}]/u', '/ +/');
        $replaceTo   = array(' ', '', ' ');

        $string = preg_replace($replaceFrom, $replaceTo, $string);
        $string = trim($string);

        return $string;
    }

    public function normalizeString($string)
    {
        $string = preg_replace('/\s+/', ' ', $string);
        $string = preg_replace('/\p{Z}+/iu', ' ', $string);
        $string = preg_replace('/\p{C}+/iu', '', $string);
        $string = trim($string);

        return $string;
    }

    /**
     * @param $content
     * @throws HelperException
     * @return string|null
     */
    public function convertContentFromCp1251ToUtf8($content)
    {
        set_error_handler(
            function ($errno, $errstr) {
                throw new HelperException('Invalid content encoding');
            },
            E_ALL | E_STRICT
        );

        try {
            $content = iconv('cp1251', 'utf-8', $content);
            if (!$this->textHelper->isUTF8($content)) {
                throw new HelperException('Invalid content encoding');
            }

            restore_error_handler();

            return $content;
        } catch (HelperException $e) {
            restore_error_handler();
            throw $e;
        }

        return null;
    }

    public function createZipContent(array $nameToContent)
    {
        $tmp = $this->tmpHelper->getTmpFile('zip');
        $this->createZipFile($nameToContent, $tmp);

        $zipContent = file_get_contents($tmp);
        unlink($tmp);

        return $zipContent;
    }

    public function createZipFile(array $nameToContent, $outputPath)
    {
        $archive = new \ZipArchive();
        $isCreated = $archive->open($outputPath, \ZipArchive::CREATE);

        if ($isCreated !== true) {
            throw new HelperException('Could not create zip archive at path "' . $outputPath . '"');
        }

        foreach ($nameToContent as $name => $content) {
            //vsafronov: workaround for bug https://bugs.php.net/bug.php?id=53948:
            $name = iconv('UTF-8', 'CP866//TRANSLIT//IGNORE', $name);
            $isAdded = $archive->addFromString($name, $content);
            if (!$isAdded) {
                throw new HelperException('Could not add content ' . 'to zip archive at path "' . $outputPath . '"');
            }
        }

        $isClosed = $archive->close();
        if (!$isClosed) {
            throw new HelperException('Could not close zip archive at path "' . $outputPath . '"');
        }
    }

    public function zipFile($inputPath, $outputPath, $fileName = null)
    {
        $archive = new \ZipArchive();
        $isCreated = $archive->open($outputPath, \ZipArchive::CREATE);

        if ($isCreated !== true) {
            throw new HelperException('Could not create zip archive at path "' . $outputPath . '"');
        }

        $fileName === null && $fileName = basename($inputPath);
        $isAdded = $archive->addFile($inputPath, $fileName);

        if (!$isAdded) {
            throw new HelperException(
                'Could not add file "' . $inputPath . '" ' . 'to zip archive at path "' . $outputPath . '"'
            );
        }

        $isClosed = $archive->close();
        if (!$isClosed) {
            throw new HelperException('Could not close zip archive at path "' . $outputPath . '"');
        }
    }

    public function zipContent($content, $fileName = null)
    {
        $inputPath = $this->tmpHelper->getTmpFile('zip_content');
        $outputPath = $this->tmpHelper->getTmpFile('zip_content') . '.zip';

        file_put_contents($inputPath, $content);
        $this->zipFile($inputPath, $outputPath, $fileName);

        $content = file_get_contents($outputPath);
        unlink($inputPath);
        unlink($outputPath);

        return $content;
    }
}
