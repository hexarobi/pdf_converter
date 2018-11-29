<?php

namespace TexasDemocrats\PdfConverter\Counties\Galveston;

use TexasDemocrats\PdfConverter\ConverterTemplate;

class Converter extends ConverterTemplate
{

    public function parsePageText($pageText) : array {

        // convert HTML entities back into standard text for better parsing results
        $pageText = html_entity_decode($pageText, ENT_QUOTES);

        $pagePattern = '([^\n]+)\n([^\n]+)Page (\d+) of (\d+)\nTotal Number of Voters : ([\d,]+) of ([\d,]+) = [\d.]+% (\d+\/\d+\/\d+ \d+:\d+ (AM|PM))\nPrecincts Reporting (\d+) of (\d+) = ([\d.]+)%\s+\nElection\s+Early\s+Absentee\s+Total\s+Party\s+Candidate(.*)';

        $matches = $this->getRegExMatches($pagePattern, $pageText);

        return [
            'main_title' => $matches[1][0],
            'sub_title' => $matches[2][0],
            'page' => $matches[3][0],
            'total pages' => $matches[4][0],
            'total number of voters' => $matches[5][0],
            'total number of potential voters' => $matches[6][0], // this is zero on the form?
            'report generated date time' => $matches[7][0],
            'precincts reporting' => $matches[9][0],
            'total precincts' => $matches[10][0],
            'races' => $this->parseRacesText($matches[12][0]),
        ];

    }

    private function parseRacesText($racesText): array
    {

        $racesPattern = '([^\n]*), Vote For 1\s+(.*?)\s+([\d,]+)\s+([\d,]+)\s+Cast Votes: [\d.]+%\s*[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+([\d,]+)';

        $matches = $this->getRegExMatches($racesPattern, $racesText);

        $results = [];

        $this->addToResults($results, 'name', $matches[1]);
        $this->addToResults($results, 'total absentee votes', $matches[5]);
        $this->addToResults($results, 'total early votes', $matches[6]);
        $this->addToResults($results, 'total election day votes', $matches[3]);
        $this->addToResults($results, 'total race votes', $matches[3]);
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

        $choicePattern = '\s*([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+([\d,]+)\s+([^\n]*)\s*([^\n\d]*)';

        $matches = $this->getRegExMatches($choicePattern, $choicesText);

        $results = [];

        $nameMatches = $matches[6];
        $partyMatches = $matches[5];

        // not every race has both a name and party listed, if no name use party for both
        if (! $nameMatches) {
            $nameMatches = $partyMatches;
        }

        $this->addToResults(
            $results,
            'name',
            $nameMatches,
            function($text) {
                return $this->cleanName($text);
            }
        );

        $this->addToResults(
            $results,
            'party',
            $partyMatches,
            function($text) {
                return $this->cleanName($text);
            }
        );

        $this->addToResults($results, 'absentee votes', $matches[3]);
        $this->addToResults($results, 'early votes', $matches[4]);
        $this->addToResults($results, 'election day votes', $matches[1]);
        $this->addToResults($results, 'total votes', $matches[2]);

        return $results;
    }

}