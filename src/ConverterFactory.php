<?php

namespace TexasDemocrats\PdfConverter;

use RuntimeException;

class ConverterFactory
{

    public static function getConverterByCounty($countyName): ConverterInterface
    {

        switch ($countyName) {
            case 'bazoria':
                return new Counties\Bazoria\Converter();
            case 'travis':
                return new Counties\Travis\Converter();
            case 'galveston':
                return new Counties\Galveston\Converter();
        }

        throw new RuntimeException('Unable to load converter for requested county');
    }

}