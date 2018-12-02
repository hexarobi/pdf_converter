<?php

namespace TexasDemocrats\PdfConverter\Counties\Travis;

use TexasDemocrats\PdfConverter\ConverterTemplate;

class Converter extends ConverterTemplate
{
    private const PAGE_PATTERN = '([^\n]*)\n([^\n]*)\n([^\n]*)\n([^\n]*)\n([^\n]*)Official Results\s+([^\n]*)\s+Precinct (\d+)\s+Total Registered Voters\s+Total registered voters in Precinct \d+\s+([\d,]+)\s+([\d,]+)\s+Total Ballots Cast in Precinct\s+% of Total Registered Voters in Precinct\s+% of Total Votes Cast in Precinct\s+Early Voting\sElection Day\sTotal Vote\s+([\d,]+)\s([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+Early Voting\sElection Day\sTotal Vote\s+(.*)Page (\d+) of (\d+)';
    private const RACE_PATTERN = '(.*?), Vote For 1\s+(.*?)\s+([\d,]+)\s+Total Votes Counted in this Race:\s+([\d,]+)\s+([\d,]+)\s+';
    private const CHOICE_PATTERN = '([\d,]+)\s+([\d,]+)\s+[\d.]+%\s+([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([^\n]+)';

    public function parsePageText($pageText) : array {
        $matches = $this->getRegExMatches(self::PAGE_PATTERN, $pageText);
        return [
            'page_title' => trim(str_replace("\n", ' ', $matches[1][0])),
            'county' => $matches[2][0],
            'election date' => $matches[3][0],
            'report generated date' => $matches[4][0],
            'title' => $matches[6][0],
            'precinct' => $matches[7][0],
            'total registered voters' => $matches[8][0],
            'total registered in precinct' => $matches[9][0],
            'total ballots cast in precinct' => $matches[10][0],
            'early voting ballots cast in precinct' => $matches[11][0],
            'election day ballots cast in precinct' => $matches[12][0],
            'page' => $matches[14][0],
            'total pages' => $matches[15][0],
            'rows' => $this->parseRacesText($matches[13][0]),
        ];
    }

    private function parseRacesText($racesText): array
    {

        $matches = $this->getRegExMatches(self::RACE_PATTERN, $racesText);

        $results = [];

        $this->addToResults($results, 'name', $matches[1]);
        $this->addToResults($results, 'total early votes', $matches[3]);
        $this->addToResults($results, 'total election day votes', $matches[4]);
        $this->addToResults($results, 'total race votes', $matches[5]);
        $this->addToResults($results, 'total election day votes', $matches[4]);
        $this->addToResults(
            $results,
            'choices',
            $matches[2],
            function($text) {
                return $this->parseChoicesText($text);
            }
        );

        return $results;
    }

    private function parseChoicesText($choicesText): array
    {

        $matches = $this->getRegExMatches(self::CHOICE_PATTERN, $choicesText);

        $results = [];

        $this->addToResults($results, 'name', $matches[4]);
        $this->addToResults($results, 'early votes', $matches[1]);
        $this->addToResults($results, 'election day votes', $matches[2]);
        $this->addToResults($results, 'total votes', $matches[3]);

        return $results;
    }

}