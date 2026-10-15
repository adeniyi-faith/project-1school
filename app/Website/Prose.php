<?php

namespace App\Website;

use Illuminate\Support\Str;

/**
 * Turns the simple writing format used in the website editor into safe HTML. Everything is escaped first, so
 * nobody can slip code into a page. Supported: a blank line starts a new paragraph, "## " a heading, "### " a
 * smaller heading, "- " a bullet list, "1. " a numbered list, "> " a quote, **bold**, *italics* and
 * [link text](https://address).
 */
class Prose
{
    public static function html(?string $text, int $headingLevel = 2): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", trim((string) $text));
        if ($text === '') {
            return '';
        }

        $out = [];
        foreach (preg_split('/\n\s*\n/', $text) as $chunk) {
            $lines = array_values(array_filter(array_map('rtrim', explode("\n", trim($chunk))), fn ($l) => $l !== ''));
            if (! $lines) {
                continue;
            }
            $first = $lines[0];
            $h2 = 'h'.$headingLevel;
            $h3 = 'h'.min(6, $headingLevel + 1);

            if (count($lines) === 1 && str_starts_with($first, '### ')) {
                $out[] = "<{$h3}>".self::inline(substr($first, 4))."</{$h3}>";
            } elseif (count($lines) === 1 && str_starts_with($first, '## ')) {
                $out[] = "<{$h2}>".self::inline(substr($first, 3))."</{$h2}>";
            } elseif (self::all($lines, '/^[-*•]\s+/')) {
                $out[] = '<ul>'.implode('', array_map(fn ($l) => '<li>'.self::inline(preg_replace('/^[-*•]\s+/', '', $l)).'</li>', $lines)).'</ul>';
            } elseif (self::all($lines, '/^\d+[.)]\s+/')) {
                $out[] = '<ol>'.implode('', array_map(fn ($l) => '<li>'.self::inline(preg_replace('/^\d+[.)]\s+/', '', $l)).'</li>', $lines)).'</ol>';
            } elseif (self::all($lines, '/^>\s?/')) {
                $out[] = '<blockquote><p>'.implode('<br>', array_map(fn ($l) => self::inline(preg_replace('/^>\s?/', '', $l)), $lines)).'</p></blockquote>';
            } else {
                // a heading line followed by text in the same chunk
                if (preg_match('/^(#{2,3}) (.+)$/', $first, $m)) {
                    $out[] = ($m[1] === '##' ? "<{$h2}>" : "<{$h3}>").self::inline($m[2]).($m[1] === '##' ? "</{$h2}>" : "</{$h3}>");
                    array_shift($lines);
                }
                if ($lines) {
                    $out[] = '<p>'.implode('<br>', array_map([self::class, 'inline'], $lines)).'</p>';
                }
            }
        }

        return implode("\n", $out);
    }

    /** The words only, for summaries, search results and counting */
    public static function plain(?string $text, int $limit = 0): string
    {
        $t = preg_replace(['/\[([^\]]+)\]\([^)]+\)/', '/^(#{2,3}|[-*•]|\d+[.)]|>)\s+/m', '/\*\*?([^*]+)\*\*?/'], ['$1', '', '$1'], (string) $text);
        $t = trim(preg_replace('/\s+/', ' ', $t));

        return $limit ? Str::limit($t, $limit, '…') : $t;
    }

    private static function all(array $lines, string $pattern): bool
    {
        foreach ($lines as $l) {
            if (! preg_match($pattern, $l)) {
                return false;
            }
        }

        return true;
    }

    private static function inline(string $line): string
    {
        $s = e($line);
        // links: only web addresses, mail and phone links, or addresses inside the site
        $s = preg_replace_callback('/\[([^\]]+)\]\(((?:https?:\/\/|mailto:|tel:|\/)[^\s)]+)\)/', function ($m) {
            $external = str_starts_with($m[2], 'http');

            return '<a href="'.$m[2].'"'.($external ? ' target="_blank" rel="noopener"' : '').'>'.$m[1].'</a>';
        }, $s);
        $s = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $s);
        $s = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $s);

        return $s;
    }

    /**
     * A headline with an accent: words written between *stars* become italic and coloured (<em>).
     * With $auto, a headline without stars gets its last three words accented instead.
     */
    public static function accent(string $text, bool $auto = false): string
    {
        $safe = e($text);
        if (preg_match('/\*[^*]+\*/', $safe)) {
            return preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe);
        }
        if (! $auto) {
            return $safe;
        }
        $words = preg_split('/\s+/', trim($safe));
        if (count($words) < 5) {
            return $safe;
        }
        $tail = implode(' ', array_splice($words, -3));

        return implode(' ', $words).' <em>'.$tail.'</em>';
    }

    /**
     * The banner headline. When the banner has "words that take turns" (one per line), the accented part of the
     * headline cycles through them (the page script does the swapping); without scripts the accented words stay.
     */
    public static function heroHeading(array $block, bool $auto = false): string
    {
        $html = self::accent((string) ($block['heading'] ?? ''), $auto);
        $phrases = collect(preg_split('/\R/', (string) ($block['phrases'] ?? '')))
            ->map(fn ($l) => trim((string) $l))->filter()->map(fn ($l) => mb_substr($l, 0, 60))->take(6)->values();

        if ($phrases->isEmpty() || ! preg_match('#<em>(.*?)</em>#', $html, $m)) {
            return $html;
        }
        $list = $phrases->prepend(html_entity_decode($m[1], ENT_QUOTES))->unique()->values();

        return preg_replace('#<em>.*?</em>#', '<em data-rotate="'.e(json_encode($list, JSON_UNESCAPED_UNICODE)).'">'.$m[1].'</em>', $html, 1);
    }
}
