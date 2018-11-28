<?php

require __DIR__ . '/vendor/autoload.php';

use TexasDemocrats\PdfConverter\ConverterFactory;

if (! isset($argv[1])) {
    echo "Please provide a county name. Example command: 'php converter.php travis'\n";
    die;
}
$countyName = $argv[1];

$countyConverter = ConverterFactory::getConverterByCounty($countyName);

$countyConverter->importPdf(null, null);