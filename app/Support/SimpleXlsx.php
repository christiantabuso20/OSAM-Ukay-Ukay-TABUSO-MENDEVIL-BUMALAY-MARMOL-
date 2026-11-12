<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Writes a real .xlsx file (no Composer package needed) from a report array.
 * Requires the PHP "zip" extension, which is enabled by default in XAMPP.
 *
 * Style ids (see styles.xml below):
 * 0 normal | 1 table header | 2 big title | 3 bold | 4 money | 5 whole number | 6 money bold | 7 number bold
 */
class SimpleXlsx
{
    /** Builds the file in the temp folder and returns its path. The caller should delete it after sending. */
    public static function build(array $report): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Excel export needs the PHP zip extension. Enable "extension=zip" in php.ini and restart Apache.');
        }

        $rows = [];
        $widthRows = [];

        $rows[] = [self::text('OSAM Ukay-Ukay', 2)];
        $rows[] = [self::text($report['title'], 3)];
        $rows[] = [self::text('Coverage: '.$report['coverage'])];
        $rows[] = [self::text('Generated: '.$report['generated'].' by '.$report['generated_by'])];
        $rows[] = [];

        foreach ($report['summary'] as $item) {
            $row = [self::text($item['label'], 3), self::typed($item['value'], $item['type'], false)];
            $rows[] = $row;
            $widthRows[] = $row;
        }
        $rows[] = [];

        $header = array_map(fn ($c) => self::text($c['label'], 1), $report['columns']);
        $rows[] = $header;
        $widthRows[] = $header;

        foreach ($report['rows'] as $data) {
            $row = [];
            foreach ($report['columns'] as $i => $col) {
                $row[] = self::typed($data[$i] ?? null, $col['type'], false);
            }
            $rows[] = $row;
            $widthRows[] = $row;
        }

        if (! empty($report['footer'])) {
            $row = [];
            foreach ($report['columns'] as $i => $col) {
                $row[] = self::typed($report['footer'][$i] ?? null, $col['type'], true);
            }
            $rows[] = $row;
            $widthRows[] = $row;
        }

        $path = tempnam(sys_get_temp_dir(), 'osam_xlsx_');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the Excel file.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($rows, $widthRows));
        $zip->close();

        return $path;
    }

    // ------------------------------------------------------------------

    private static function text(?string $value, int $style = 0): array
    {
        return ['kind' => 'text', 'value' => (string) $value, 'style' => $style];
    }

    private static function typed(mixed $value, string $type, bool $bold): array
    {
        if ($value === null || $value === '') {
            return ['kind' => 'empty', 'value' => '', 'style' => 0];
        }

        if ($type === 'money') {
            return ['kind' => 'number', 'value' => number_format((float) $value, 2, '.', ''), 'style' => $bold ? 6 : 4, 'display' => '₱'.number_format((float) $value, 2)];
        }

        if ($type === 'int') {
            return ['kind' => 'number', 'value' => (string) (int) $value, 'style' => $bold ? 7 : 5, 'display' => number_format((int) $value)];
        }

        return self::text((string) $value, $bold ? 3 : 0);
    }

    private static function column(int $index): string
    {
        $letters = '';
        $n = $index + 1;

        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $letters = chr(65 + $mod).$letters;
            $n = intdiv($n - 1, 26);
        }

        return $letters;
    }

    private static function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';

        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function sheet(array $rows, array $widthRows): string
    {
        // Column widths from the summary + table rows only (not the long title lines).
        $widths = [];
        foreach ($widthRows as $row) {
            foreach ($row as $i => $cell) {
                $len = mb_strlen($cell['display'] ?? $cell['value']);
                $widths[$i] = max($widths[$i] ?? 10, $len + 2);
            }
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="'.$n.'" max="'.$n.'" width="'.min(50, $w).'" customWidth="1"/>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<cols>'.$cols.'</cols><sheetData>';

        foreach ($rows as $r => $row) {
            $rowNumber = $r + 1;
            $xml .= '<row r="'.$rowNumber.'">';

            foreach ($row as $i => $cell) {
                if ($cell['kind'] === 'empty') {
                    continue;
                }

                $ref = self::column($i).$rowNumber;

                if ($cell['kind'] === 'number') {
                    $xml .= '<c r="'.$ref.'" s="'.$cell['style'].'"><v>'.$cell['value'].'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" s="'.$cell['style'].'" t="inlineStr"><is><t xml:space="preserve">'.self::escape($cell['value']).'</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;₱&quot;#,##0.00"/></numFmts>'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="16"/><color rgb="FF713B50"/><name val="Calibri"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF713B50"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="8">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="164" fontId="3" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            .'<xf numFmtId="3" fontId="3" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }
}
