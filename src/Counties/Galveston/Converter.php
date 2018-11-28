<?php

namespace TexasDemocrats\PdfConverter\Counties\Galveston;

use RuntimeException;
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
            'rows' => $this->parseRowText($matches[12][0]),
        ];

    }

    private function parseRowText($rowText): array
    {

        $rowPattern = '([^\n]*), Vote For 1\s+(.*?)\s+([\d,]+)\s+([\d,]+)\s+Cast Votes: [\d.]+%\s*[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+([\d,]+)';

        $matches = $this->getRegExMatches($rowPattern, $rowText);

        $results = [];

        foreach ($matches[1] as $raceIndex=>$raceName) {
            // init an array at this index
            $results[$raceIndex] = [];
            $results[$raceIndex]['name'] = $raceName;
        }

        foreach ($matches[5] as $raceIndex=>$raceEarlyVotes) {
            $results[$raceIndex]['total absentee votes'] = $raceEarlyVotes;
        }

        foreach ($matches[6] as $raceIndex=>$raceEarlyVotes) {
            $results[$raceIndex]['total early votes'] = $raceEarlyVotes;
        }

        foreach ($matches[3] as $raceIndex=>$raceElectionDayVotes) {
            $results[$raceIndex]['total election day votes'] = $raceElectionDayVotes;
        }

        foreach ($matches[4] as $raceIndex=>$raceVotes) {
            $results[$raceIndex]['total race votes'] = $raceVotes;
        }

        foreach ($matches[2] as $raceIndex=>$raceChoicesText) {
            $results[$raceIndex]['choices'] = $this->parseChoicesText($raceChoicesText);
        }

        return $results;
    }

    private function parseChoicesText($choicesText): array
    {

        $choicePattern = '\s*([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+([\d,]+)\s+([^\n]*)\s*([^\n\d]*)';

        $matches = $this->getRegExMatches($choicePattern, $choicesText);

        $results = [];

        // not every race has both a name and party listed
        if ($matches[6]) {

            foreach ($matches[6] as $choiceIndex=>$choiceName) {
                $results[$choiceIndex] = [];
                $results[$choiceIndex]['name'] = $this->cleanName($choiceName);
            }
            foreach ($matches[5] as $choiceIndex=>$choiceName) {
                $results[$choiceIndex]['party'] = $this->cleanName($choiceName);
            }

        } else {
            foreach ($matches[5] as $choiceIndex=>$choiceName) {
                $results[$choiceIndex] = [];
                $results[$choiceIndex]['name'] = $this->cleanName($choiceName);
                $results[$choiceIndex]['party'] = $this->cleanName($choiceName);
            }
        }

        foreach ($matches[3] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['absentee votes'] = $choiceEarly;
        }

        foreach ($matches[4] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['early votes'] = $choiceEarly;
        }

        foreach ($matches[1] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['election day votes'] = $choiceEarly;
        }

        foreach ($matches[2] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['total votes'] = $choiceEarly;
        }

        return $results;
    }

}