<?php

namespace App\Services;

use ZipArchive;

/**
 * Real, dependency-free export generation — no PhpSpreadsheet, no
 * dompdf/mpdf, because `composer install` is blocked in this sandbox
 * (the same standing constraint every prior sprint has hit). Rather
 * than fake these formats or skip the feature, this builds genuinely
 * valid output using only what PHP ships with:
 *
 * - CSV: trivial, always real.
 * - XLSX: a minimal but spec-valid single-sheet OOXML workbook, built
 *   with ZipArchive (a core PHP extension, confirmed available in this
 *   environment) — no styling, no multiple sheets, no formulas, but a
 *   real file Excel/Sheets/LibreOffice opens correctly, not a renamed
 *   CSV.
 * - PDF: a minimal but spec-valid single/multi-page PDF (PDF 1.4,
 *   Helvetica base-14 font, real object/xref/trailer structure),
 *   built by hand since no PDF library is available. Tabular reports
 *   only — this is not a general-purpose PDF engine.
 */
class ReportExportService
{
    /**
     * @param string[] $columns
     * @param array<int, array<string, mixed>> $rows each row keyed by column
     */
    public function toCsv(array $columns, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($col) => $row[$col] ?? '', $columns));
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * @param string[] $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public function toXlsx(array $columns, array $rows): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmpPath, ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheet($columns, $rows));

        $zip->close();
        $bytes = file_get_contents($tmpPath);
        unlink($tmpPath);

        return $bytes;
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    private function xlsxSheet(array $columns, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= '<row r="1">';
        foreach ($columns as $i => $col) {
            $xml .= $this->xlsxCell($this->columnLetter($i).'1', $col, true);
        }
        $xml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 2;
            $xml .= "<row r=\"{$r}\">";
            foreach ($columns as $i => $col) {
                $value = $row[$col] ?? '';
                $xml .= $this->xlsxCell($this->columnLetter($i).$r, $value, ! is_numeric($value));
            }
            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
    }

    private function xlsxCell(string $ref, mixed $value, bool $asString): string
    {
        if ($asString) {
            $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            return "<c r=\"{$ref}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
        }

        return "<c r=\"{$ref}\"><v>{$value}</v></c>";
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - $mod, 26);
        }

        return $letter;
    }

    /**
     * @param string[] $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public function toPdf(string $title, array $columns, array $rows, ?string $subtitle = null): string
    {
        $pageWidth = 595;
        $pageHeight = 842;
        $margin = 40;
        $rowHeight = 16;
        $usableWidth = $pageWidth - (2 * $margin);
        $colWidth = $usableWidth / max(count($columns), 1);

        $pages = [];
        $lines = [];
        $lines[] = ['text' => $this->pdfEscape($title), 'size' => 16, 'bold' => true];
        if ($subtitle) {
            $lines[] = ['text' => $this->pdfEscape($subtitle), 'size' => 10, 'bold' => false];
        }
        $lines[] = ['header' => array_map(fn ($c) => $this->pdfEscape((string) $c), $columns)];
        foreach ($rows as $row) {
            $lines[] = ['row' => array_map(fn ($c) => $this->pdfEscape((string) ($row[$c] ?? '')), $columns)];
        }

        $y = $pageHeight - $margin;
        $currentPageLines = [];
        foreach ($lines as $line) {
            $needed = isset($line['text']) ? ($line['size'] + 8) : $rowHeight;
            if ($y - $needed < $margin) {
                $pages[] = $currentPageLines;
                $currentPageLines = [];
                $y = $pageHeight - $margin;
            }
            $line['y'] = $y;
            $currentPageLines[] = $line;
            $y -= $needed;
        }
        if ($currentPageLines) {
            $pages[] = $currentPageLines;
        }

        return $this->assemblePdf($pages, $pageWidth, $pageHeight, $margin, $colWidth);
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function assemblePdf(array $pages, int $pageWidth, int $pageHeight, int $margin, float $colWidth): string
    {
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";

        $pageIds = [];
        $contentIds = [];
        $nextId = 4; // 1=Catalog, 2=Pages, 3=Font

        foreach ($pages as $pageLines) {
            $pageIds[] = $nextId;
            $contentIds[] = $nextId + 1;
            $nextId += 2;
        }

        $kids = implode(' ', array_map(fn ($id) => "{$id} 0 R", $pageIds));
        $objects[2] = "<< /Type /Pages /Kids [{$kids}] /Count ".count($pageIds)." >>";
        $objects[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        foreach ($pages as $i => $pageLines) {
            $pageId = $pageIds[$i];
            $contentId = $contentIds[$i];

            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] ".
                "/Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";

            $stream = "BT\n";
            foreach ($pageLines as $line) {
                if (isset($line['text'])) {
                    $size = $line['size'];
                    $stream .= "/F1 {$size} Tf\n{$margin} {$line['y']} Td ({$line['text']}) Tj\n";
                    $stream .= "-{$margin} -{$line['y']} Td\n"; // reset to origin for next absolute positioning
                } elseif (isset($line['header'])) {
                    $x = $margin;
                    foreach ($line['header'] as $cell) {
                        $stream .= "/F1 9 Tf\n{$x} {$line['y']} Td ({$cell}) Tj\n-{$x} -{$line['y']} Td\n";
                        $x += $colWidth;
                    }
                } elseif (isset($line['row'])) {
                    $x = $margin;
                    foreach ($line['row'] as $cell) {
                        $stream .= "/F1 8 Tf\n{$x} {$line['y']} Td ({$cell}) Tj\n-{$x} -{$line['y']} Td\n";
                        $x += $colWidth;
                    }
                }
            }
            $stream .= "ET";

            $length = strlen($stream);
            $objects[$contentId] = "<< /Length {$length} >>\nstream\n{$stream}\nendstream";
        }

        return $this->writePdfBytes($objects);
    }

    private function writePdfBytes(array $objects): string
    {
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 ".($maxId + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".($maxId + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
