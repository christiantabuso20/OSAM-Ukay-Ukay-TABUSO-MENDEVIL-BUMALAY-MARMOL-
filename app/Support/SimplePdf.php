<?php

namespace App\Support;

use App\Services\ReportBuilder;

/**
 * Writes a real PDF file (no Composer package needed) from a report array.
 * Uses the built-in Helvetica font, so the peso sign is written as "PHP ".
 */
class SimplePdf
{
    private const BERRY = [0.443, 0.231, 0.314];     // #713B50
    private const DARK = [0.200, 0.165, 0.176];      // #332A2D
    private const MUTED = [0.549, 0.522, 0.529];     // #8C8587
    private const VANILLA = [0.961, 0.941, 0.910];   // #F5F0E8
    private const BEIGE = [0.910, 0.863, 0.796];     // #E8DCCB

    private float $pw;
    private float $ph;
    private float $margin = 30;
    private array $pages = [];
    private string $buf = '';
    private float $y = 0;
    private array $report;

    /** @return string the raw PDF bytes */
    public static function build(array $report): string
    {
        return (new self())->render($report);
    }

    private function render(array $report): string
    {
        $this->report = $report;
        $landscape = count($report['columns']) > 7;
        [$this->pw, $this->ph] = $landscape ? [841.89, 595.28] : [595.28, 841.89];

        $this->newPage();
        $this->header();
        $this->summary();
        $this->table();

        $this->pages[] = $this->buf;

        return $this->assemble();
    }

    // ---- layout ----

    private function newPage(): void
    {
        if ($this->buf !== '') {
            $this->pages[] = $this->buf;
        }
        $this->buf = '';
        $this->y = $this->ph - $this->margin;
    }

    private function header(): void
    {
        $r = $this->report;
        $w = $this->pw - 2 * $this->margin;

        $this->text($this->margin, $this->y - 18, 'OSAM Ukay-Ukay', 20, true, self::BERRY);
        $this->y -= 30;
        $this->text($this->margin, $this->y - 8, 'Clothing Inventory Monitoring and Reporting System', 8, false, self::MUTED);
        $this->y -= 20;
        $this->text($this->margin, $this->y - 12, $r['title'], 14, true, self::DARK);
        $this->y -= 20;
        foreach (['Coverage: '.$r['coverage'], 'Generated: '.$r['generated'].' by '.$r['generated_by']] as $line) {
            foreach ($this->wrap($line, $w, 8.5) as $l) {
                $this->text($this->margin, $this->y - 9, $l, 8.5, false, self::DARK);
                $this->y -= 12;
            }
        }
        $this->rect($this->margin, $this->y - 2, $w, 2.5, self::BERRY);
        $this->y -= 16;
    }

    private function summary(): void
    {
        $labelW = 170;
        $valueW = 110;
        foreach ($this->report['summary'] as $item) {
            $this->rect($this->margin, $this->y - 16, $labelW, 16, self::VANILLA);
            $this->text($this->margin + 6, $this->y - 11.5, $item['label'], 9, true, self::BERRY);
            $val = $this->fmt($item['value'], $item['type']);
            $this->text($this->margin + $labelW + $valueW - 6 - $this->width($val, 9, true), $this->y - 11.5, $val, 9, true, self::DARK);
            $this->line($this->margin, $this->y - 16, $this->margin + $labelW + $valueW, $this->y - 16, self::BEIGE);
            $this->y -= 16;
        }
        $this->y -= 14;
    }

    private function table(): void
    {
        $cols = $this->report['columns'];
        $widths = $this->columnWidths();
        $fs = 7.5;
        $pad = 4;

        $this->tableHead($cols, $widths, $fs, $pad);

        if (count($this->report['rows']) === 0) {
            $this->text($this->margin, $this->y - 16, 'No records found for this report.', 9, false, self::MUTED);
            $this->y -= 24;
        }

        foreach ($this->report['rows'] as $row) {
            $cells = [];
            $lines = 1;
            foreach ($cols as $i => $col) {
                $cells[$i] = $this->wrap($this->fmt($row[$i] ?? null, $col['type']), $widths[$i] - 2 * $pad, $fs);
                $lines = max($lines, count($cells[$i]));
            }
            $h = $lines * ($fs + 2.5) + 2 * $pad - 1;

            if ($this->y - $h < $this->margin + 18) {
                $this->newPage();
                $this->tableHead($cols, $widths, $fs, $pad);
            }
            $this->drawRow($cols, $widths, $cells, $h, $fs, $pad, false);
        }

        if (! empty($this->report['footer']) && count($this->report['rows']) > 0) {
            $cells = [];
            foreach ($cols as $i => $col) {
                $cells[$i] = [$this->fmt($this->report['footer'][$i] ?? null, $col['type'])];
            }
            $h = $fs + 2 * $pad + 1.5;
            if ($this->y - $h < $this->margin + 18) {
                $this->newPage();
            }
            $this->drawRow($cols, $widths, $cells, $h, $fs, $pad, true);
        }
    }

    private function tableHead(array $cols, array $widths, float $fs, float $pad): void
    {
        $h = $fs + 2 * $pad + 2;
        $this->rect($this->margin, $this->y - $h, array_sum($widths), $h, self::BERRY);
        $x = $this->margin;
        foreach ($cols as $i => $col) {
            $label = $this->wrap($col['label'], $widths[$i] - 2 * $pad, $fs)[0] ?? '';
            $tx = $col['type'] === 'text' ? $x + $pad : $x + $widths[$i] - $pad - $this->width($label, $fs, true);
            $this->text($tx, $this->y - $pad - $fs + 0.5, $label, $fs, true, [1, 1, 1]);
            $x += $widths[$i];
        }
        $this->y -= $h;
    }

    private function drawRow(array $cols, array $widths, array $cells, float $h, float $fs, float $pad, bool $bold): void
    {
        if ($bold) {
            $this->rect($this->margin, $this->y - $h, array_sum($widths), $h, self::VANILLA);
            $this->line($this->margin, $this->y, $this->margin + array_sum($widths), $this->y, self::BERRY, 1.2);
        }
        $x = $this->margin;
        foreach ($cols as $i => $col) {
            foreach ($cells[$i] as $n => $line) {
                $tx = $col['type'] === 'text' ? $x + $pad : $x + $widths[$i] - $pad - $this->width($line, $fs, $bold);
                $this->text($tx, $this->y - $pad - $fs + 0.5 - $n * ($fs + 2.5), $line, $fs, $bold, self::DARK);
            }
            $x += $widths[$i];
        }
        $this->line($this->margin, $this->y - $h, $this->margin + array_sum($widths), $this->y - $h, self::BEIGE);
        $this->y -= $h;
    }

    /** Column widths proportional to the longest content (capped), scaled to the page width. */
    private function columnWidths(): array
    {
        $cols = $this->report['columns'];
        $want = [];
        foreach ($cols as $i => $col) {
            $max = mb_strlen($col['label']);
            foreach ($this->report['rows'] as $row) {
                $max = max($max, min(mb_strlen($this->fmt($row[$i] ?? null, $col['type'])), 40));
            }
            $want[$i] = max(6, min($max, 40));
        }
        $total = array_sum($want);
        $avail = $this->pw - 2 * $this->margin;

        return array_map(fn ($w) => $avail * $w / $total, $want);
    }

    // ---- text helpers ----

    private function fmt(mixed $value, string $type): string
    {
        return str_replace('₱', 'PHP ', (string) ReportBuilder::format($value, $type));
    }

    /** UTF-8 -> Windows-1252 so the built-in font can show accents. Unknown characters become "?". */
    private function encode(string $s): string
    {
        $s = str_replace(['–', '—', '’', '‘', '“', '”', '•', '…'], ['-', '-', "'", "'", '"', '"', '*', '...'], $s);
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s);

        return $out === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $out;
    }

    private function width(string $s, float $fs, bool $bold = false): float
    {
        // Average Helvetica glyph width, slightly wider for bold.
        return mb_strlen($s) * $fs * ($bold ? 0.56 : 0.52);
    }

    /** @return string[] */
    private function wrap(string $text, float $maxWidth, float $fs): array
    {
        $perLine = max(3, (int) floor($maxWidth / ($fs * 0.52)));
        $lines = [];
        $current = '';

        foreach (preg_split('/\s+/u', trim($text)) ?: [''] as $word) {
            while (mb_strlen($word) > $perLine) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $lines[] = mb_substr($word, 0, $perLine);
                $word = mb_substr($word, $perLine);
            }
            $try = $current === '' ? $word : $current.' '.$word;
            if (mb_strlen($try) > $perLine) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        $lines[] = $current;

        return $lines;
    }

    // ---- drawing primitives ----

    private function text(float $x, float $y, string $s, float $fs, bool $bold, array $rgb): void
    {
        $s = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $this->encode($s));
        $this->buf .= sprintf("BT %.3F %.3F %.3F rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $rgb[0], $rgb[1], $rgb[2], $bold ? 'F2' : 'F1', $fs, $x, $y, $s);
    }

    private function rect(float $x, float $y, float $w, float $h, array $rgb): void
    {
        $this->buf .= sprintf("%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f\n", $rgb[0], $rgb[1], $rgb[2], $x, $y, $w, $h);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, array $rgb, float $width = 0.5): void
    {
        $this->buf .= sprintf("%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n", $rgb[0], $rgb[1], $rgb[2], $width, $x1, $y1, $x2, $y2);
    }

    // ---- file structure ----

    private function assemble(): string
    {
        $count = count($this->pages);
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        for ($i = 0; $i < $count; $i++) {
            $kids[] = (5 + $i * 2).' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.$count.' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pages as $i => $content) {
            $page = 5 + $i * 2;
            $footer = sprintf("BT 0.549 0.522 0.529 rg /F1 7 Tf %.2F %.2F Td (Page %d of %d) Tj ET\n", $this->pw / 2 - 20, 16, $i + 1, $count);
            $content .= $footer;
            $objects[$page] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>', $this->pw, $this->ph, $page + 1);
            $objects[$page + 1] = '<< /Length '.strlen($content)." >>\nstream\n".$content."endstream";
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        ksort($objects);
        foreach ($objects as $n => $body) {
            $offsets[$n] = strlen($pdf);
            $pdf .= "$n 0 obj\n$body\nendobj\n";
        }

        $xref = strlen($pdf);
        $max = max(array_keys($objects));
        $pdf .= "xref\n0 ".($max + 1)."\n0000000000 65535 f \n";
        for ($n = 1; $n <= $max; $n++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $pdf .= "trailer\n<< /Size ".($max + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        return $pdf;
    }
}
