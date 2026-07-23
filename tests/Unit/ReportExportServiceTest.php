<?php

namespace Tests\Unit;

use App\Services\ReportExportService;
use Tests\TestCase;

/**
 * No framework dependencies in ReportExportService itself, so this
 * runs as a plain unit test — but the assertions are real structural
 * checks (a valid zip signature for XLSX, a valid PDF header/trailer),
 * not just "did it return a non-empty string".
 */
class ReportExportServiceTest extends TestCase
{
    private function service(): ReportExportService
    {
        return new ReportExportService();
    }

    public function test_csv_export_produces_real_comma_separated_content_with_a_header_row(): void
    {
        $csv = $this->service()->toCsv(['Name', 'Amount'], [['Name' => 'Item A', 'Amount' => 10]]);

        $this->assertStringContainsString('Name,Amount', $csv);
        $this->assertStringContainsString('Item A,10', $csv);
    }

    public function test_xlsx_export_produces_a_structurally_valid_zip_with_the_required_ooxml_parts(): void
    {
        $bytes = $this->service()->toXlsx(['Name'], [['Name' => 'Item A']]);

        // A real zip file signature (PK\x03\x04), not a renamed CSV.
        $this->assertEquals("PK\x03\x04", substr($bytes, 0, 4));

        $tmp = tempnam(sys_get_temp_dir(), 'xlsxtest');
        file_put_contents($tmp, $bytes);
        $zip = new \ZipArchive();
        $opened = $zip->open($tmp);
        $this->assertTrue($opened === true);
        $this->assertNotFalse($zip->locateName('xl/worksheets/sheet1.xml'));
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'));
        $zip->close();
        unlink($tmp);
    }

    public function test_pdf_export_produces_a_structurally_valid_pdf_with_correct_header_and_trailer(): void
    {
        $pdf = $this->service()->toPdf('Test Report', ['Name'], [['Name' => 'Item A']]);

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringEndsWith('%%EOF', trim($pdf));
        $this->assertStringContainsString('/Type /Catalog', $pdf);
        $this->assertStringContainsString('xref', $pdf);
    }

    public function test_pdf_export_paginates_across_multiple_pages_for_a_large_row_count(): void
    {
        $rows = [];
        for ($i = 1; $i <= 80; $i++) {
            $rows[] = ['Item' => "Product {$i}"];
        }

        $pdf = $this->service()->toPdf('Large Report', ['Item'], $rows);

        // Two Page objects means real pagination occurred, not one giant overflowing page.
        $this->assertEquals(2, substr_count($pdf, '/Type /Page '));
    }
}
