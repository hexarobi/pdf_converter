<?php

namespace TexasDemocrats\PdfConverter\Counties\Bazoria;

use RuntimeException;
use TexasDemocrats\PdfConverter\ConverterTemplate;

class Converter extends ConverterTemplate
{

    public function run($inputPdfFile): void
    {
        $this->importPdf($inputPdfFile);
    }

    public function parsePageText($pageText) : array
    {
        return [
            'headers' => $this->parsePageHeader($pageText),
            'rows' => $this->parsePageRows($pageText),
        ];
    }

    private function parsePageHeader($pageText)
    {

        $matchPattern = '/.*Overvotes:\s+\d+\s+\d+\s+\d+\s+\d+\s+(.*)Run Time\s+(\d+:\d+\s(AM|PM))\s+Run Date\s+(\d+\/\d+\/\d+)\s+([\w ]+)\s+(\d+\/\d+\/\d+)\s+Page (\d+) of (\d+)\s+(.+)Registered Voters\s+(\d+) of (\d+) = [\d.]+ %\s+(\d+) of (\d+) = [\d.]+ %\s+Polling Places Reporting\s+(\d+)/ms';
        if (!preg_match_all($matchPattern, $pageText, $matches)) {
            throw new RuntimeException('Could not match page header');
        }

        $data = [
            'title' => trim(str_replace("\n", " ", $matches[1][0])),
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
        ];

        return $data;
    }

    private function parsePageRows($pageText)
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

        $matchPattern = '/([^\n]+)\s+Choice\s+Party\s+Absentee\s+Early Voting\s+Election Day\s+Total\s+(.*)(Cast Votes:.*Undervotes:\s+\d+\s+\d+\s+\d+\s+\d+\s+Overvotes:\s+\d+\s+\d+\s+\d+\s+\d+)/ms';
        if (!preg_match_all($matchPattern, $rowText, $matches)) {
            throw new RuntimeException('Could not match any result rows');
        }

        $data = [
            'race' => $matches[1][0],
            'choices' => $this->parseChoices($matches[2][0]),
            'summary' => $this->parseSummary($matches[3][0]),
        ];

        return $data;
    }

    private function parseChoices($choicesText)
    {

        $matchPattern = '/([^%]*)\n\s+(REP|DEM|LIB)\s+(\d+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%\s+([\d\.]+)\s+[\d\.]+%/ms';
        if (!preg_match_all($matchPattern, $choicesText, $matches)) {
            throw new RuntimeException('Could not match any choices');
        }

        $choices = [];

        foreach ($matches[1] as $choiceIndex => $choiceName) {
            // init as array
            $choices[$choiceIndex] = [];
            $choices[$choiceIndex]['name'] = $this->cleanName($choiceName);
        }

        foreach ($matches[2] as $choiceIndex => $choiceParty) {
            $choices[$choiceIndex]['party'] = $choiceParty;
        }

        foreach ($matches[3] as $choiceIndex => $choiceAbsenteeVotes) {
            $choices[$choiceIndex]['absentee'] = $choiceAbsenteeVotes;
        }

        foreach ($matches[4] as $choiceIndex => $choiceEarlyVotes) {
            $choices[$choiceIndex]['early'] = $choiceEarlyVotes;
        }

        foreach ($matches[5] as $choiceIndex => $choiceElectionDayVotes) {
            $choices[$choiceIndex]['election_day'] = $choiceElectionDayVotes;
        }

        foreach ($matches[6] as $choiceIndex => $choiceTotalVotes) {
            $choices[$choiceIndex]['total'] = $choiceTotalVotes;
        }

        return $choices;
    }

    private function parseSummary($summaryText)
    {

        $matchPattern = '/Cast Votes:\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+(\d+)\s+[\d\.]+%\s+Undervotes:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+Overvotes:\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/ms';
        if (!preg_match_all($matchPattern, $summaryText, $matches)) {
            throw new RuntimeException('Could not match any choices');
        }

        $summary = [
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

        return $summary;
    }

}