<?php

namespace TexasDemocrats\PdfConverter;

use Closure;
use DirectoryIterator;
use Exception;
use RuntimeException;

abstract class ConverterTemplate implements ConverterInterface {

    /**
     * @param $inputPdfFile
     * @param null $limitPages
     */
    public function importPdf($inputPdfFile = null, $limitPages = null): void
    {
        try {

            if (! $inputPdfFile) {
                $inputPdfFile = $this->findFirstPdfInFolder();
            }

            $this->info("Loading PDF from $inputPdfFile");

            $pages = $this->loadPagesFromPdfFile($inputPdfFile);
            if (! $pages) {
                throw new RuntimeException(
                    'Could not load any pages from the PDF file: "' . $inputPdfFile . '"'
                );
            }

            $results = [];

            foreach ($pages as $pageCounter=>$page) {
                $this->info("Processing page $pageCounter");

                // County-specific converter logic takes over here
                $results[] = $this->parsePageText($page->getText());

                if ($limitPages
                    && $pageCounter >= ($limitPages+1)
                ) {
                    $this->info("Page limit ($limitPages) reached.");
                    break;
                }
            }

            $newFilename = $this->changeFilenameExtension($inputPdfFile, 'json');
            $this->outputResults($results, $newFilename);
            $this->info("Output written to $newFilename");

        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
        }

        $this->info('Done');
    }

    /**
     * @param $inputPdfFile
     * @return array
     * @throws Exception
     */
    private function loadPagesFromPdfFile($inputPdfFile): array
    {
        // Parse pdf file and build necessary objects.
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($inputPdfFile);

        // Retrieve an array of all pages from the pdf file.
        return $pdf->getPages();
    }

    private function findFirstPdfInFolder() {

        $classPath = \get_class($this);
        $classPathPieces = explode('\\', $classPath);
        $folderPath = __DIR__ . '/Counties/' . $classPathPieces[3];

        $this->info("Checking for PDF in folder " . $folderPath);

        $dir = new DirectoryIterator($folderPath);
        foreach ($dir as $fileinfo) {
            if (! $fileinfo->isDot()
                && strtolower($fileinfo->getExtension()) === 'pdf'
            ) {
                return $fileinfo->getRealPath();
            }
        }

        throw new RuntimeException('Could not load a PDF file in the county directory');
    }

    /**
     * @param $results
     * @param $outputFilepath
     */
    private function outputResults($results, $outputFilepath): void
    {
        $fp = fopen($outputFilepath, 'w');
        fwrite($fp, json_encode($results, JSON_PRETTY_PRINT));
        fclose($fp);
    }

    /**
     * @param $message
     */
    protected function info($message): void {
        echo date('[Y-m-d H:i:s] ') . " $message\n";
    }

    /**
     * @param $name
     * @return string
     */
    protected function cleanName($name) {
        $name = str_replace("\n", " ", $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * @param $filename
     * @param $new_extension
     * @return string
     */
    private function changeFilenameExtension($filename, $new_extension): string
    {
        $info = pathinfo($filename);
        return $info['dirname'] . '/' . $info['filename'] . '.' . $new_extension;
    }

    /**
     * @param $pattern
     * @param $text
     * @return mixed
     */
    protected function getRegExMatches($pattern, $text) {
        if (! preg_match_all("/$pattern/ms", $text, $matches)) {
            throw new RuntimeException('Could not match pattern');
        }
        return $matches;
    }

    /**
     * @param $results
     * @param $fieldName
     * @param array $array
     * @param Closure|null $parser
     */
    public function addToResults(&$results, $fieldName, array $array, Closure $parser = null) {
        foreach ($array as $key=>$value) {

            // init result at key
            if (! isset($results[$key])) {
                $results[$key] = [];
            }

            if ($parser) {
                $value = $parser($value);
            }

            $results[$key][$fieldName] = $value;
        }
    }

}