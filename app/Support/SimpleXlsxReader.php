<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/** Reads the first sheet of an .xlsx file as rows of text. Enough for simple uploads; no spreadsheet library needed. */
class SimpleXlsxReader
{
    /** @return list<list<string>> */
    public static function rows(string $path, int $maxRows = 5000): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('This file could not be opened as an Excel file.');
        }

        $shared = [];
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml !== false) {
            foreach (self::load($xml)->si as $si) {
                // Plain text sits in <t>; formatted text is split into runs <r><t>
                $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map('strval', $si->xpath('.//*[local-name()="t"]') ?: []));
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('This Excel file has no first sheet.');
        }

        $rows = [];
        foreach (self::load($sheet)->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $col = self::column((string) $c['r']);
                $type = (string) $c['t'];
                $value = match ($type) {
                    's' => $shared[(int) $c->v] ?? '',
                    'inlineStr' => (string) ($c->is->t ?? ''),
                    default => (string) $c->v,
                };
                $cells[$col] = trim($value);
            }
            if ($cells) {
                $line = array_fill(0, max(array_keys($cells)) + 1, '');
                foreach ($cells as $i => $v) {
                    $line[$i] = $v;
                }
                $rows[] = $line;
            }
            if (count($rows) >= $maxRows) {
                break;
            }
        }

        return $rows;
    }

    private static function load(string $xml): SimpleXMLElement
    {
        $doc = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
        if ($doc === false) {
            throw new RuntimeException('This Excel file is damaged.');
        }

        return $doc;
    }

    /** "C7" => 2 */
    private static function column(string $ref): int
    {
        $letters = preg_replace('/\d/', '', $ref);
        $n = 0;
        foreach (str_split($letters) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }
}
