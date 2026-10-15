<?php

namespace App\Console\Commands;

use App\Website\Themes;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** php artisan site:make-theme "Sunrise" --base=modern: a new website look built on an existing one */
class MakeSiteTheme extends Command
{
    protected $signature = 'site:make-theme {name : The theme name, for example "Sunrise"} {--base=heritage : The built-in theme whose layout to start from}';
    protected $description = 'Create a new website theme (theme.json + style.css) under resources/website-themes';

    public function handle(): int
    {
        $key = Str::slug((string) $this->argument('name'));
        $base = (string) $this->option('base');
        if (! preg_match('/^[a-z][a-z0-9-]{1,30}$/', $key) || isset(Themes::ALL[$key])) {
            $this->error('Pick a different name: letters and numbers, and not one of the built-in themes.');

            return self::FAILURE;
        }
        if (! isset(Themes::ALL[$base])) {
            $this->error('The base must be one of: '.implode(', ', array_keys(Themes::ALL)));

            return self::FAILURE;
        }
        $dir = resource_path('website-themes/'.$key);
        if (is_dir($dir)) {
            $this->error("A theme called {$key} already exists.");

            return self::FAILURE;
        }

        $t = Themes::ALL[$base];
        mkdir($dir, 0775, true);
        file_put_contents($dir.'/theme.json', json_encode([
            'label' => Str::headline($key), 'note' => 'Describe the look in one sentence.', 'base' => $base,
            'brand' => $t['brand'], 'brand2' => $t['brand2'], 'bg' => $t['bg'], 'soft' => $t['soft'], 'text' => $t['text'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        file_put_contents($dir.'/style.css', "/* Extra CSS for the {$key} theme, added after the {$base} styles. It only applies to sites using this theme.\n   Scope rules with body[data-site-theme=\"{$key}\"] and use the colour variables (--brand, --brand2, --bg, --text). */\nbody[data-site-theme=\"{$key}\"] .btn{border-radius:999px}\n");

        $this->info("Made the \"{$key}\" theme (based on {$base}) in {$dir}");
        $this->line('It now appears in Website > Themes for every school. Change the colours in theme.json and the rest in style.css.');

        return self::SUCCESS;
    }
}
