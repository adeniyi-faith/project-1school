<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * A small Excel (.xlsx) writer: one or more sheets of rows, numbers kept as numbers,
 * bold heading rows, merged cells, frozen heading rows and column widths. Enough for
 * broadsheets without adding a spreadsheet library to the project.
 *
 *   $x = new SimpleXlsx();
 *   $x->sheet('JSS 1', $rows, ['bold_rows' => [0, 1], 'freeze_rows' => 2, 'freeze_cols' => 2, 'merges' => ['C1:F1'], 'widths' => [1 => 28]]);
 *   $bytes = $x->bytes();
 */
class SimpleXlsx
{
    /** @var array<int, array{name: string, rows: array, options: array}> */
    private array $sheets = [];

    public function sheet(string $name, array $rows, array $options = []): self
    {
        $this->sheets[] = ['name' => $this->sheetName($name), 'rows' => $rows, 'options' => $options];

        return $this;
    }

    public function bytes(): string
    {
        if (! $this->sheets) {
            $this->sheet('Sheet1', []);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not build the Excel file.');
        }

        $count = count($this->sheets);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .implode('', array_map(fn ($i) => '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', range(1, $count)))
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'
            .implode('', array_map(fn ($s, $i) => '<sheet name="'.$this->esc($s['name']).'" sheetId="'.($i + 1).'" r:id="rId'.($i + 1).'"/>', $this->sheets, array_keys($this->sheets)))
            .'</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .implode('', array_map(fn ($i) => '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>', range(1, $count)))
            .'<Relationship Id="rId'.($count + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
        // Style 0: normal. Style 1: bold, light grey fill, centred and wrapped (headings).
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf></cellXfs>'
            .'</styleSheet>');

        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($i + 1).'.xml', $this->sheetXml($sheet['rows'], $sheet['options']));
        }
        $zip->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    private function sheetXml(array $rows, array $options): string
    {
        $bold = array_flip($options['bold_rows'] ?? []);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        $freezeRows = (int) ($options['freeze_rows'] ?? 0);
        $freezeCols = (int) ($options['freeze_cols'] ?? 0);
        if ($freezeRows || $freezeCols) {
            $cell = self::column($freezeCols + 1).($freezeRows + 1);
            $xml .= '<sheetViews><sheetView workbookViewId="0"><pane'
                .($freezeCols ? ' xSplit="'.$freezeCols.'"' : '').($freezeRows ? ' ySplit="'.$freezeRows.'"' : '')
                .' topLeftCell="'.$cell.'" activePane="bottomRight" state="frozen"/></sheetView></sheetViews>';
        }

        if (! empty($options['widths'])) {
            $xml .= '<cols>';
            foreach ($options['widths'] as $col => $width) {
                $xml .= '<col min="'.$col.'" max="'.$col.'" width="'.(float) $width.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';
        foreach (array_values($rows) as $r => $row) {
            $xml .= '<row r="'.($r + 1).'">';
            foreach (array_values($row) as $c => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $ref = self::column($c + 1).($r + 1);
                $style = isset($bold[$r]) ? ' s="1"' : '';
                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="'.$ref.'"'.$style.'><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'"'.$style.' t="inlineStr"><is><t xml:space="preserve">'.$this->esc((string) $value).'</t></is></c>';
                }
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData>';

        if (! empty($options['merges'])) {
            $xml .= '<mergeCells count="'.count($options['merges']).'">'
                .implode('', array_map(fn ($m) => '<mergeCell ref="'.$m.'"/>', $options['merges']))
                .'</mergeCells>';
        }

        return $xml.'</worksheet>';
    }

    /** 1 → A, 27 → AA */
    public static function column(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $m = ($n - 1) % 26;
            $s = chr(65 + $m).$s;
            $n = intdiv($n - 1, 26);
        }

        return $s;
    }

    /** Excel sheet names: at most 31 characters, none of : \ / ? * [ ], and no repeats */
    private function sheetName(string $name): string
    {
        $base = mb_substr(trim(preg_replace('/[:\\\\\/?*\[\]]+/', '-', $name) ?? '') ?: 'Sheet', 0, 31);
        $taken = array_map(fn ($s) => mb_strtolower($s['name']), $this->sheets);
        $candidate = $base;
        for ($i = 2; in_array(mb_strtolower($candidate), $taken, true); $i++) {
            $candidate = mb_substr($base, 0, 31 - strlen(" ($i)"))." ($i)";
        }

        return $candidate;
    }

    private function esc(string $s): string
    {
        // Drop control characters Excel refuses, then escape for XML
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s) ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
