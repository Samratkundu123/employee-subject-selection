<?php
// Brainware University Employee Subject Selection System
// Native Excel (.xlsx) & CSV Parser Service
declare(strict_types=1);

class ExcelImport
{
    /**
     * Parses an uploaded .xlsx or .csv file and returns an array of subjects:
     * [['subject_code' => 'SUB-01', 'subject_name' => 'Database Management System'], ...]
     */
    public static function parseSubjectsFile(string $filePath, string $originalFilename): array
    {
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return self::parseCsv($filePath);
        }

        if ($ext === 'xlsx') {
            return self::parseXlsx($filePath);
        }

        throw new InvalidArgumentException("Unsupported file type (.{$ext}). Please upload an Excel (.xlsx) or CSV (.csv) file.");
    }

    private static function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new RuntimeException("Could not open uploaded file for reading.");
        }

        $rows = [];
        $isFirst = true;
        $codeCol = 0;
        $nameCol = 1;

        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $cleanRow = array_map('trim', $row);
            if (empty(array_filter($cleanRow))) {
                continue;
            }

            if ($isFirst) {
                $isFirst = false;
                $header0 = strtolower($cleanRow[0] ?? '');
                $header1 = strtolower($cleanRow[1] ?? '');
                if (str_contains($header0, 'code') || str_contains($header0, 'subject') || str_contains($header1, 'name')) {
                    if (str_contains($header0, 'name') && str_contains($header1, 'code')) {
                        $nameCol = 0;
                        $codeCol = 1;
                    }
                    continue; // Skip header row
                }
            }

            $code = $cleanRow[$codeCol] ?? '';
            $name = $cleanRow[$nameCol] ?? '';

            // If only one column was provided, treat it as the subject name
            if ($name === '' && $code !== '') {
                $name = $code;
                $code = '';
            }

            if ($name !== '') {
                $rows[] = [
                    'code' => $code,
                    'name' => $name
                ];
            }
        }

        fclose($handle);

        return self::normalizeRows($rows);
    }

    private static function parseXlsx(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException("PHP ZipArchive extension is required to process .xlsx files.");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new RuntimeException("Failed to read the .xlsx archive. The file may be corrupted.");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $xmlObj = simplexml_load_string($sharedXml);
            if ($xmlObj) {
                foreach ($xmlObj->si as $val) {
                    if (isset($val->t)) {
                        $sharedStrings[] = (string)$val->t;
                    } elseif (isset($val->r)) {
                        $text = '';
                        foreach ($val->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read sheet data
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException("Could not find worksheet data in the uploaded Excel file.");
        }

        $xml = simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData)) {
            throw new RuntimeException("Invalid Excel worksheet structure.");
        }

        $rawRows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $cellRef = (string)$c['r']; // e.g. "A1", "B2"
                $type = (string)$c['t'];
                $val = (string)$c->v;

                if ($type === 's' && isset($sharedStrings[(int)$val])) {
                    $val = $sharedStrings[(int)$val];
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string)$c->is->t;
                }

                preg_match('/^([A-Z]+)/', $cellRef, $matches);
                $colLetters = $matches[1] ?? 'A';
                $colIndex = self::colLetterToIndex($colLetters);

                $cells[$colIndex] = trim($val);
            }
            if (!empty($cells)) {
                ksort($cells);
                $rawRows[] = $cells;
            }
        }

        if (empty($rawRows)) {
            return [];
        }

        // Check if first row is header
        $firstRow = $rawRows[0];
        $startIdx = 0;
        $val0 = strtolower($firstRow[0] ?? '');
        $val1 = strtolower($firstRow[1] ?? '');

        $codeCol = 0;
        $nameCol = 1;

        if (str_contains($val0, 'code') || str_contains($val0, 'subject') || str_contains($val1, 'name')) {
            $startIdx = 1;
            if (str_contains($val0, 'name') && str_contains($val1, 'code')) {
                $nameCol = 0;
                $codeCol = 1;
            }
        }

        $rows = [];
        for ($i = $startIdx; $i < count($rawRows); $i++) {
            $r = $rawRows[$i];
            $code = $r[$codeCol] ?? '';
            $name = $r[$nameCol] ?? '';

            if ($name === '' && $code !== '') {
                $name = $code;
                $code = '';
            }

            if ($name !== '') {
                $rows[] = [
                    'code' => $code,
                    'name' => $name
                ];
            }
        }

        return self::normalizeRows($rows);
    }

    private static function colLetterToIndex(string $letters): int
    {
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    private static function normalizeRows(array $rows): array
    {
        $normalized = [];
        $counter = 1;

        foreach ($rows as $item) {
            $name = trim($item['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $code = trim($item['code'] ?? '');
            if ($code === '') {
                $code = sprintf('SUB-%02d', $counter);
            }

            $normalized[] = [
                'subject_code' => $code,
                'subject_name' => $name
            ];
            $counter++;
        }

        return $normalized;
    }
}
