<?php

namespace App\Services;

/**
 * Reads the end of the app's log files and folds repeats into groups ("this error, 143 times, last seen 10:42").
 * Only the last few megabytes are read so a huge log cannot exhaust a small shared host's memory.
 */
class ErrorLogReader
{
    private const WINDOW_BYTES = 3 * 1024 * 1024;
    private const MAX_FILES = 3;

    public const PROBLEM_LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning'];
    private const HEADER = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})(?:\.\d+)?(?:[+-]\d{2}:?\d{2})?\] [\w.\-]+\.(\w+): (.*)$/';

    /** @return array{groups: array<int, array>, total: int, files: array<int, array{name:string,size:string,partial:bool}>} */
    public function read(?string $search = null, string $level = 'problems'): array
    {
        $files = collect(glob(storage_path('logs/*.log')) ?: [])
            ->sortByDesc(fn ($f) => filemtime($f))->take(self::MAX_FILES)->values();

        $groups = [];
        $total = 0;
        $info = [];
        foreach ($files as $file) {
            $size = filesize($file);
            $info[] = ['name' => basename($file), 'size' => $this->bytes($size), 'partial' => $size > self::WINDOW_BYTES];
            foreach ($this->entries($file, $size) as $entry) {
                if (! $this->wanted($entry, $search, $level)) {
                    continue;
                }
                $total++;
                $key = $entry['level'].'|'.$entry['class'].'|'.$this->normalise($entry['message']);
                $g = &$groups[$key];
                if (! isset($g)) {
                    $g = ['key' => md5($key), 'level' => $entry['level'], 'class' => $entry['class'], 'count' => 0, 'first_seen' => $entry['time'], 'last_seen' => $entry['time'], 'message' => $entry['message'], 'where' => $entry['where'], 'trace' => $entry['trace']];
                }
                $g['count']++;
                if ($entry['time'] >= $g['last_seen']) {
                    $g['last_seen'] = $entry['time'];
                    $g['message'] = $entry['message'];
                    $g['trace'] = $entry['trace'];
                    $g['where'] = $entry['where'];
                }
                $g['first_seen'] = min($g['first_seen'], $entry['time']);
                unset($g);
            }
        }

        $list = collect($groups)->sortByDesc('last_seen')->values()->take(200)->all();

        return ['groups' => $list, 'total' => $total, 'files' => $info];
    }

    /** @return \Generator<array{time:string, level:string, class:?string, message:string, where:?string, trace:string}> */
    private function entries(string $file, int $size): \Generator
    {
        $h = @fopen($file, 'r');
        if (! $h) {
            return;
        }
        if ($size > self::WINDOW_BYTES) {
            fseek($h, $size - self::WINDOW_BYTES);
            fgets($h); // drop the half line we landed in
        }

        $cur = null;
        while (($line = fgets($h)) !== false) {
            $line = rtrim($line, "\r\n");
            if (preg_match(self::HEADER, $line, $m)) {
                if ($cur) {
                    yield $this->finish($cur);
                }
                $cur = ['time' => str_replace('T', ' ', $m[1]), 'level' => strtolower($m[2]), 'head' => $m[3], 'body' => []];
            } elseif ($cur && count($cur['body']) < 40) {
                $cur['body'][] = $line;
            }
        }
        if ($cur) {
            yield $this->finish($cur);
        }
        fclose($h);
    }

    private function finish(array $c): array
    {
        $head = $c['head'];
        $class = null;
        $where = null;
        $message = $head;

        // Laravel writes: message {"exception":"[object] (Class(code: 0): text at /path/File.php:12)
        if (($pos = strpos($head, '{"exception":"[object] (')) !== false) {
            $message = trim(substr($head, 0, $pos));
            if (preg_match('/\[object\] \(([^\s(]+)\(code: [^)]*\): (.*?) at (\S+?:\d+)\)/', $head, $m)) {
                $class = $m[1];
                $where = $m[3];
                $message = $message !== '' ? $message : $m[2];
            }
        }
        $trace = trim(implode("\n", $c['body']));

        return [
            'time' => $c['time'], 'level' => $c['level'], 'class' => $class,
            'message' => $this->tidy($message), 'where' => $where ? $this->tidy($where) : null,
            'trace' => $this->tidy(implode("\n", array_slice(explode("\n", $trace), 0, 25))),
        ];
    }

    private function wanted(array $e, ?string $search, string $level): bool
    {
        if ($level === 'problems' && ! in_array($e['level'], self::PROBLEM_LEVELS, true)) {
            return false;
        }
        if ($level === 'errors' && ! in_array($e['level'], ['emergency', 'alert', 'critical', 'error'], true)) {
            return false;
        }
        if ($level === 'warnings' && $e['level'] !== 'warning') {
            return false;
        }

        return $search === null || $search === '' || stripos($e['message'].' '.$e['class'].' '.$e['where'].' '.$e['trace'], $search) !== false;
    }

    /** Two lines that differ only in an id, number or time are the same error */
    private function normalise(string $message): string
    {
        $m = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '#', $message);

        return mb_substr(preg_replace('/\d+/', '#', $m), 0, 200);
    }

    /** Short paths, and nothing that looks like a secret leaves the server */
    private function tidy(string $text): string
    {
        $text = str_replace(base_path().'/', '', $text);
        $text = preg_replace('/(password|passwd|secret|token|api[_-]?key|authorization|bearer)(["\']?\s*[:=]\s*["\']?|\s+)[^\s"\',}]{4,}/i', '$1$2[hidden]', $text);

        return mb_substr($text, 0, 4000);
    }

    private function bytes(int $b): string
    {
        return $b >= 1048576 ? round($b / 1048576, 1).' MB' : max(1, round($b / 1024)).' KB';
    }
}
