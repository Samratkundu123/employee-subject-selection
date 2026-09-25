<?php
// Brainware University Employee Subject Selection System
// High-Performance Native OpenXML Excel (.xlsx) Generator
// Zero external dependencies, 100% PHP 8+ and Vercel compatible
declare(strict_types=1);

require_once __DIR__ . '/../models/Faculty.php';

class ExcelExport
{
    public static function generateAndDownload(): void
    {
        // 1. Fetch complete data from master faculty list
        $data = Faculty::getAllWithSubmissionStatus(null, 'all');

        $headers = [
            'Sl. No.',
            'Faculty Name',
            'Faculty Email',
            'Employee Code',
            'Subject 1',
            'Subject 2',
            'Subject 3',
            'Subject 4',
            'Subject 5',
            'Submission Date',
            'Submission Time',
            'Status'
        ];

        $colWidths = [
            10, // Sl. No.
            28, // Faculty Name
            36, // Faculty Email
            18, // Employee Code
            32, // Subject 1
            32, // Subject 2
            32, // Subject 3
            32, // Subject 4
            32, // Subject 5
            18, // Submission Date
            18, // Submission Time
            16  // Status
        ];

        $rows = [];
        foreach ($data as $item) {
            $rows[] = [
                (string)$item['sl_no'],
                (string)$item['faculty_name'],
                (string)$item['faculty_email'],
                (string)$item['employee_code'],
                (string)$item['subject_1'],
                (string)$item['subject_2'],
                (string)$item['subject_3'],
                (string)$item['subject_4'],
                (string)$item['subject_5'],
                (string)$item['submission_date'],
                (string)$item['submission_time'],
                (string)$item['status']
            ];
        }

        $xlsxContent = self::buildXlsx($headers, $rows, $colWidths);

        $dateStamp = date('d-m-Y');
        $filename = "Faculty_Subject_Selection_{$dateStamp}.xlsx";

        // Clean any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($xlsxContent));
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $xlsxContent;
        exit;
    }

    public static function buildXlsx(array $headers, array $rows, array $colWidths): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'bwu_xlsx_');
        $zip = new ZipArchive();
        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create temporary Excel ZIP archive.");
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rootRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rootRels);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<bookViews><workbookView xWindow="0" yWindow="0" windowWidth="22000" windowHeight="12000"/></bookViews>' .
            '<sheets><sheet name="Faculty Selections" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/styles.xml (Colors: Navy Blue #003366 header, white bold text, borders)
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="2">' .
            '<font><sz val="11"/><color rgb="FF1F2937"/><name val="Segoe UI"/></font>' .
            '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Segoe UI"/></font>' .
            '</fonts>' .
            '<fills count="3">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF003366"/></patternFill></fill>' .
            '</fills>' .
            '<borders count="2">' .
            '<border><left/><right/><top/><bottom/></border>' .
            '<border>' .
            '<left style="thin"><color rgb="FFD1D5DB"/></left>' .
            '<right style="thin"><color rgb="FFD1D5DB"/></right>' .
            '<top style="thin"><color rgb="FFD1D5DB"/></top>' .
            '<bottom style="thin"><color rgb="FFD1D5DB"/></bottom>' .
            '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>' .
            '</cellStyleXfs>' .
            '<cellXfs count="3">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0"/>' . // 0: Default data cell
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 1: Header
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 2: Centered data cell
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // xl/worksheets/sheet1.xml
        $totalRows = count($rows) + 1;
        $totalCols = count($headers);
        $lastColLetter = self::colLetter($totalCols);

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<sheetViews><sheetView tabSelected="1" workbookViewId="0">' .
            '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' .
            '</sheetView></sheetViews>';

        // Column widths
        $sheet .= '<cols>';
        foreach ($colWidths as $i => $width) {
            $colNum = $i + 1;
            $sheet .= '<col min="' . $colNum . '" max="' . $colNum . '" width="' . $width . '" customWidth="1"/>';
        }
        $sheet .= '</cols>';

        $sheet .= '<sheetData>';

        // Row 1: Headers
        $sheet .= '<row r="1" ht="28" customHeight="1">';
        foreach ($headers as $colIdx => $headerText) {
            $cellRef = self::colLetter($colIdx + 1) . '1';
            $escaped = htmlspecialchars($headerText, ENT_XML1, 'UTF-8');
            $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="1"><is><t>' . $escaped . '</t></is></c>';
        }
        $sheet .= '</row>';

        // Data Rows
        $rowNum = 2;
        foreach ($rows as $row) {
            $sheet .= '<row r="' . $rowNum . '" ht="22" customHeight="1">';
            foreach ($row as $colIdx => $cellVal) {
                $cellRef = self::colLetter($colIdx + 1) . $rowNum;
                $cellStr = ($cellVal === null) ? '' : (string)$cellVal;
                $escaped = htmlspecialchars($cellStr, ENT_XML1, 'UTF-8');
                // Center Sl No, Dates, Status, Employee Code
                $style = in_array($colIdx, [0, 3, 9, 10, 11], true) ? '2' : '0';
                $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="' . $style . '"><is><t>' . $escaped . '</t></is></c>';
            }
            $sheet .= '</row>';
            $rowNum++;
        }

        $sheet .= '</sheetData>';

        // Enable Auto-Filter on entire header span
        $sheet .= '<autoFilter ref="A1:' . $lastColLetter . $totalRows . '"/>';
        $sheet .= '</worksheet>';

        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $content = file_get_contents($tempFile);
        @unlink($tempFile);

        return $content;
    }

    private static function colLetter(int $colNumber): string
    {
        $letter = '';
        while ($colNumber > 0) {
            $mod = ($colNumber - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $colNumber = (int)(($colNumber - $mod) / 26);
        }
        return $letter;
    }
}
