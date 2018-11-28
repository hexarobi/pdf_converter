<?php

namespace TexasDemocrats\PdfConverter\Counties\Travis;

use RuntimeException;
use TexasDemocrats\PdfConverter\ConverterTemplate;

class Converter extends ConverterTemplate
{

    public function parsePageText($pageText) : array {

        $matchPattern = '/([^\n]*)\n([^\n]*)\n([^\n]*)\n([^\n]*)\n([^\n]*)Official Results\s+([^\n]*)\s+Precinct (\d+)\s+Total Registered Voters\s+Total registered voters in Precinct \d+\s+([\d,]+)\s+([\d,]+)\s+Total Ballots Cast in Precinct\s+% of Total Registered Voters in Precinct\s+% of Total Votes Cast in Precinct\s+Early Voting\sElection Day\sTotal Vote\s+([\d,]+)\s([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+[\d.]+%\s+([\d,]+)\s+Early Voting\sElection Day\sTotal Vote\s+(.*)Page (\d+) of (\d+)/ms';
        if (! preg_match_all($matchPattern, $pageText, $matches)) {
            throw new RuntimeException('Could not match page header');
        }

        $data = [
            'page_title' => trim(str_replace("\n", " ", $matches[1][0])),
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
            'rows' => $this->parsePageRows($matches[13][0]),
        ];

        return $data;
    }

    private function parsePageRows($pageRowsText): array
    {

        $matchPattern = '/(.*?), Vote For 1\s+(.*?)\s+([\d,]+)\s+Total Votes Counted in this Race:\s+([\d,]+)\s+([\d,]+)\s+/ms';
        if (! preg_match_all($matchPattern, $pageRowsText, $matches)) {
            throw new RuntimeException('Could not match any page rows: "' . $pageRowsText . '"');
        }

        $results = [];

        foreach ($matches[1] as $raceIndex=>$raceName) {
            $results[$raceIndex] = [];
            $results[$raceIndex]['name'] = $raceName;
        }

        foreach ($matches[3] as $raceIndex=>$raceEarlyVotes) {
            $results[$raceIndex]['total early votes'] = $raceEarlyVotes;
        }

        foreach ($matches[4] as $raceIndex=>$raceElectionDayVotes) {
            $results[$raceIndex]['total election day votes'] = $raceElectionDayVotes;
        }

        foreach ($matches[5] as $raceIndex=>$raceVotes) {
            $results[$raceIndex]['total race votes'] = $raceVotes;
        }

        foreach ($matches[2] as $raceIndex=>$raceChoices) {
            $results[$raceIndex]['choices'] = $this->parseRaceChoices($raceChoices);
        }

        return $results;
    }

    private function parseRaceChoices($raceChoices): array
    {

        $matchPattern = '/([\d,]+)\s+([\d,]+)\s+[\d.]+%\s+([\d,]+)\s+[\d.]+%\s+[\d.]+%\s+([^\n]+)/ms';
        if (! preg_match_all($matchPattern, $raceChoices, $matches)) {
            throw new RuntimeException('Could not match any race choices: "' . $raceChoices . '"');
        }

        $results = [];

        foreach ($matches[4] as $choiceIndex=>$choiceName) {
            $results[$choiceIndex] = [];
            $results[$choiceIndex]['name'] = $choiceName;
        }

        foreach ($matches[1] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['early votes'] = $choiceEarly;
        }

        foreach ($matches[2] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['election day votes'] = $choiceEarly;
        }

        foreach ($matches[3] as $choiceIndex=>$choiceEarly) {
            $results[$choiceIndex]['total votes'] = $choiceEarly;
        }

        return $results;
    }

}