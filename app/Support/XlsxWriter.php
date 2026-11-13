<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis XLSX minimal (Office Open XML) tanpa pustaka tambahan, cukup ekstensi zip.
 * - Teks ditulis sebagai inline string, jadi isian peserta yang diawali "=" tidak pernah jadi rumus.
 * - Angka (termasuk teks angka tanpa nol di depan) ditulis sebagai angka; nilai kosong/null dibiarkan sel kosong.
 * - Baris pertama tiap sheet adalah header tebal dan dibekukan.
 */
final class XlsxWriter
{
    /** @var array<int, array{name: string, path: string}> */
    private array $sheets = [];

    public static function available(): bool
    {
        return class_exists(ZipArchive::class);
    }

    public function addSheet(string $name, array $header, iterable $rows): self
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $fh = fopen($path, 'w');
        $cols = max(1, count($header));
        fwrite($fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="'.$cols.'" width="20" customWidth="1"/></cols><sheetData>');
        fwrite($fh, $this->row(1, $header, 1));
        $r = 1;
        foreach ($rows as $row) {
            fwrite($fh, $this->row(++$r, array_values($row), 0));
        }
        fwrite($fh, '</sheetData></worksheet>');
        fclose($fh);

        $this->sheets[] = ['name' => $this->sheetName($name), 'path' => $path];

        return $this;
    }

    /** Menulis workbook ke file sementara dan mengembalikan path-nya. */
    public function save(): string
    {
        if (! self::available()) {
            throw new RuntimeException('Ekstensi PHP zip tidak tersedia.');
        }
        $file = tempnam(sys_get_temp_dir(), 'ritnova').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $overrides = $rels = $entries = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $zip->addFile($sheet['path'], "xl/worksheets/sheet{$n}.xml");
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $rels .= '<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
            $entries .= '<sheet name="'.self::esc($sheet['name']).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
        }
        $styles = count($this->sheets) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides.'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$entries.'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$rels
            .'<Relationship Id="rId'.$styles.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>');
        $zip->close();

        foreach ($this->sheets as $sheet) {
            @unlink($sheet['path']);
        }

        return $file;
    }

    public static function column(int $index): string
    {
        $name = '';
        for ($i = $index + 1; $i > 0; $i = intdiv($i - 1, 26)) {
            $name = chr(65 + ($i - 1) % 26).$name;
        }

        return $name;
    }

    private function row(int $r, array $values, int $style): string
    {
        $xml = '<row r="'.$r.'">';
        foreach ($values as $c => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $ref = self::column($c).$r;
            $s = $style ? ' s="'.$style.'"' : '';
            if (is_bool($v)) {
                $v = (int) $v;
            }
            if (is_string($v) && preg_match('/^-?(0|[1-9]\d{0,14})(\.\d{1,10})?$/', $v)) {
                $v = str_contains($v, '.') ? (float) $v : (int) $v; // angka dari database yang terbaca sebagai teks
            }
            if ((is_int($v) || is_float($v)) && is_finite((float) $v)) {
                $xml .= '<c r="'.$ref.'"'.$s.'><v>'.$v.'</v></c>';
            } else {
                $xml .= '<c r="'.$ref.'"'.$s.' t="inlineStr"><is><t xml:space="preserve">'.self::esc((string) $v).'</t></is></c>';
            }
        }

        return $xml.'</row>';
    }

    private function sheetName(string $name): string
    {
        $name = mb_substr(trim(str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $name)), 0, 31) ?: 'Sheet';
        $taken = array_column($this->sheets, 'name');
        $base = $name;
        for ($i = 2; in_array($name, $taken, true); $i++) {
            $name = mb_substr($base, 0, 28).' '.$i;
        }

        return $name;
    }

    private static function esc(string $s): string
    {
        $s = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
