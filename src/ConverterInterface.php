<?php

namespace TexasDemocrats\PdfConverter;

interface ConverterInterface {

    /**
     * Accept the raw text of a single page of the PDF document
     * and return a JSON structure that represents the same data
     *
     * @param $pageText
     * @return array
     */
    public function parsePageText($pageText): array;

    /**
     * The root function processed by all converters.
     * Most converters will use the template's version of this function and only implement parsePageText
     * but it can be overridden by converters as needed.
     *
     * @param $inputPdfFile
     * @param null $limitPages
     * @return mixed
     */
    public function importPdf($inputPdfFile = null, $limitPages = null);

}