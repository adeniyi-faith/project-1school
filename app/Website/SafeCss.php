<?php

namespace App\Website;

/**
 * Makes CSS from a school or a theme folder safe to put in a page. It can restyle the site but cannot run
 * anything: no tags, no @import, no scripts, and addresses in url() must be https or inline pictures.
 */
class SafeCss
{
    public static function clean(string $css, int $max = 8000): string
    {
        $css = mb_substr(str_replace(["\0", "\r"], '', $css), 0, $max);
        // comments can hide what follows, so remove them first
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? '';
        // a backslash can spell a forbidden word (\75 rl), so none are kept
        $css = str_replace('\\', '', $css);
        // anything that looks like a tag is dropped
        $css = preg_replace('/<[^>]*>?/', '', $css) ?? '';
        $css = str_replace(['<', '>'], '', $css);
        // rules that load other files or run code
        $css = preg_replace('/@(import|charset|namespace)\b[^;{}]*;?/i', '', $css) ?? '';
        $css = preg_replace('/(expression|behavior|-moz-binding|javascript:|vbscript:)/i', '', $css) ?? '';
        // url(): only https addresses and small inline pictures stay
        $css = preg_replace_callback('/url\(\s*([^)]*)\)/i', function ($m) {
            $v = trim($m[1], " \t\n'\"");

            return preg_match('#^(https://[^\s\'"()]+|data:image/(png|jpeg|jpg|gif|webp|svg\+xml);base64,[A-Za-z0-9+/=]+)$#i', $v) ? 'url("'.$v.'")' : 'none';
        }, $css) ?? '';

        return trim($css);
    }
}
