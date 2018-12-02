<?php

namespace TexasDemocrats\PdfConverter\Counties\Bazoria;

use RuntimeException;
use TexasDemocrats\PdfConverter\ConverterTemplate;

class Converter extends ConverterTemplate
{
    private const PAGE_PATTERN = '.*Overvotes:\s+\d+\s+\d+\s+\d+\s+\d+\s+(.*)Run Time\s+(\d+:\d+\s(AM|PM))\s+Run Date\s+(\d+\/\d+\/\d+)\s+([\w ]+)\s+(\d+\/\d+\/\d+)\s+Page (\d+) of (\d+)\s+(.+)Registered Voters\s+(\d+) of (\d+) = [\d.]+ %\s+(\d+) of (\d+) = [\d.]+ %\s+Polling Places Reporting\s+(\d+)';
    private const RACE_PATTERN = '([^\n]+)\s+Choice\s+Party\s+Absentee\s+Early Voting\s+Election Day\s+Total\s+(.*)(Cast Votes:.*Undervotes:\s+\d+\s+\d+\s+\d+\s+\d+\s+Overvotes:\s+\d+\s+\d+\s+\d+\s+\d+)';
    private const CHOICES_PATTERN = '([^%]*)\n\s+(REP|DEM|LIB)\s+(\d+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%';
    private const SUMMARY_PATTERN = 'Cast Votes:\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+Undervotes:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+Overvotes:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)';

    public function parsePageText($pageText) : array {

        $matches = $this->getRegExMatches(self::PAGE_PATTERN, $pageText);

        return [
            'title' => trim(str_replace("\n", ' ', $matches[1][0])),
            'run time' => $matches[2][0],
            'run date' => $matches[4][0],
            'county' => $matches[5][0],
            'election date' => $matches[6][0],
            'page' => $matches[7][0],
            'total pages' => $matches[8][0],
            'registered_voters_total' => $matches[11][0],
            'registered_voters_cast_ballot' => $matches[10][0],
            'polling_places_total' => $matches[13][0],
            'polling_places_reporting' => $matches[12][0],
            'precinct' => $matches[14][0],
            'races' => $this->parseRacesText($pageText),
        ];

    }

    private function parseRacesText($pageText): array
    {
        // Match each section that begins with "Choice" and ends with "Overvotes"
        $matchPattern = '/[^\n]+\s+Choice.+?Overvotes:\s+\d+\s+\d+\s+\d+\s+\d+/ms';
        if (!preg_match_all($matchPattern, $pageText, $resultRows)) {
            throw new RuntimeException('Could not match any result rows');
        }

        $results = [];

        foreach ($resultRows[0] as $rowCounter => $resultRow) {
            $data = $this->parseResultRow($resultRow);
            $results[] = $data;
        }

        return $results;
    }

    private function parseResultRow($rowText): array
    {
        $matches = $this->getRegExMatches(self::RACE_PATTERN, $rowText);
        return [
            'race' => $matches[1][0],
            'choices' => $this->parseChoices($matches[2][0]),
            'summary' => $this->parseSummary($matches[3][0]),
        ];
    }

    private function parseChoices($choicesText): array
    {
        $matches = $this->getRegExMatches(self::CHOICES_PATTERN, $choicesText);

        $results = [];

        $this->addToResults(
            $results,
            'name',
            $matches[1],
            function($text) {
                return $this->cleanName($text);
            }
        );
        $this->addToResults($results, 'party', $matches[2]);
        $this->addToResults($results, 'absentee', $matches[3]);
        $this->addToResults($results, 'early', $matches[4]);
        $this->addToResults($results, 'election_day', $matches[5]);
        $this->addToResults($results, 'total', $matches[6]);

        return $results;
    }

    private function parseSummary($summaryText): array
    {
        $matches = $this->getRegExMatches(self::SUMMARY_PATTERN, $summaryText);
        return [
            'total votes cast' => [
                'absentee' => $matches[1][0],
                'early' => $matches[2][0],
                'election day' => $matches[3][0],
                'total' => $matches[4][0],
            ],
            'undervotes' => [
                'absentee' => $matches[5][0],
                'early' => $matches[6][0],
                'election day' => $matches[7][0],
                'total' => $matches[8][0],
            ],
            'overvotes' => [
                'absentee' => $matches[9][0],
                'early' => $matches[10][0],
                'election day' => $matches[11][0],
                'total' => $matches[12][0],
            ],
        ];
    }

}