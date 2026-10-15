<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/** php artisan site:make-block "Price list": makes a new website section as two small files */
class MakeSiteBlock extends Command
{
    protected $signature = 'site:make-block {name : The section name, for example "Price list"}';
    protected $description = 'Create a new website section (block.json + view.blade.php) under resources/website-blocks';

    public function handle(): int
    {
        $type = Str::snake(Str::slug((string) $this->argument('name'), '_'));
        if (! preg_match('/^[a-z][a-z0-9_]{1,30}$/', $type)) {
            $this->error('Use a name of letters and numbers, for example "Price list".');

            return self::FAILURE;
        }
        $dir = resource_path('website-blocks/'.$type);
        if (is_dir($dir)) {
            $this->error("A section called {$type} already exists at {$dir}.");

            return self::FAILURE;
        }
        if (array_key_exists($type, \App\Website\Blocks::types())) {
            $this->error("{$type} is already a section. Pick another name.");

            return self::FAILURE;
        }

        mkdir($dir, 0775, true);
        $label = Str::headline($type);
        file_put_contents($dir.'/block.json', json_encode([
            'label' => $label,
            'help' => 'Describe this section for the people who add it to a page.',
            'fields' => [
                ['key' => 'heading', 'label' => 'Heading', 'kind' => 'text', 'max' => 120],
                ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 1000],
                ['key' => 'items', 'label' => 'Items', 'kind' => 'list', 'limit' => 8, 'noun' => 'item', 'fields' => [
                    ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                    ['key' => 'text', 'label' => 'Text', 'kind' => 'textarea', 'max' => 300],
                ]],
            ],
            'default' => ['heading' => $label, 'body' => '', 'items' => []],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        file_put_contents($dir.'/view.blade.php', <<<'BLADE'
{{-- $b holds this section's boxes; $school, $site and $theme are available too. Use the site's own classes
     (sec, wrap, sec-head, card, card-body, btn) and colour variables (var(--brand), var(--brand2)) so every theme suits it. --}}
<section class="sec"><div class="wrap">
    @if ($b['heading'])<div class="sec-head"><h2>{{ $b['heading'] }}</h2></div>@endif
    @if ($b['body'])<p>{{ $b['body'] }}</p>@endif
    <div class="live-grid">
        @foreach ($b['items'] as $item)
            <div class="card card--plain"><div class="card-body"><h3>{{ $item['title'] }}</h3><p>{{ $item['text'] }}</p></div></div>
        @endforeach
    </div>
</div></section>
BLADE
        );

        $this->info("Made the \"{$label}\" section in {$dir}");
        $this->line('It now appears in the section list of every school\'s page editor. Edit block.json for the boxes and view.blade.php for the look.');

        return self::SUCCESS;
    }
}
