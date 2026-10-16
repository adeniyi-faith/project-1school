<?php

namespace App\Website;

use App\Models\SiteForm;

/**
 * The sections a website page is made of. Each section type says what its boxes are called, what it starts with
 * and which view draws it. Adding a new kind of section means one entry here and one view in
 * resources/views/website/blocks.
 */
class Blocks
{
    private static ?array $found = null;

    public const MAX_BLOCKS = 30;
    /** Page addresses the website uses for itself */
    public const RESERVED_SLUGS = ['news', 'sitemap', 'feed', 'gallery', 'apply', 'login', 'sign-in', 'search', 'admin'];

    /** The colour band a section sits on */
    public const BANDS = ['plain' => 'Plain', 'soft' => 'Soft tint', 'dark' => 'Deep colour'];

    public const BUTTONS = ['none' => 'No button', 'apply' => 'Apply online', 'login' => 'Sign in', 'contact' => 'Contact us'];

    /** Small pictures a card can start with (drawn by website/partials/icon.blade.php) */
    public const ICONS = [
        'none' => 'No icon', 'book' => 'Open book', 'heart' => 'Heart', 'shield' => 'Shield', 'star' => 'Star', 'users' => 'People',
        'laptop' => 'Computer', 'trophy' => 'Trophy', 'cap' => 'Graduation cap', 'sprout' => 'Growing plant', 'palette' => 'Art',
        'chat' => 'Chat', 'clipboard' => 'Checklist', 'user' => 'Person',
    ];

    /** Built-in sections plus any a developer added under resources/website-blocks (see custom()) */
    public static function types(): array
    {
        return self::builtIn() + self::custom();
    }

    /** field kinds: text, textarea, select (options), number, image (an uploaded picture), list (repeating rows of fields) */
    private static function builtIn(): array
    {
        $heading = fn (string $label = 'Heading') => ['key' => 'heading', 'label' => $label, 'kind' => 'text', 'max' => 120];

        return [
            'hero' => [
                'label' => 'Top banner', 'help' => 'The big first impression: a large heading over a photo, with buttons.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small line above the heading', 'kind' => 'text', 'max' => 80],
                    $heading('Big heading'),
                    ['key' => 'lead', 'label' => 'Short line in italics (only the Statement theme shows it)', 'kind' => 'text', 'max' => 160],
                    ['key' => 'phrases', 'label' => 'Words that take turns in the heading (optional)', 'kind' => 'textarea', 'max' => 300, 'hint' => 'One per line, up to 6. They swap in place of the words you put between *stars* in the heading, for example: Where every child is *known*. Leave empty to keep the heading still.'],
                    ['key' => 'subheading', 'label' => 'One line, or a short paragraph, under it', 'kind' => 'textarea', 'max' => 400],
                    ['key' => 'image', 'label' => 'Photo', 'kind' => 'image', 'hint' => 'A wide, bright photo works best. Without one, your colours are used.'],
                    ['key' => 'foot', 'label' => 'Line at the bottom of the banner (only the Statement theme shows it)', 'kind' => 'text', 'max' => 80, 'hint' => 'For example a motto. Shown next to a row of small dots.'],
                    ['key' => 'facts', 'label' => 'Details card (only the Statement theme shows it)', 'kind' => 'list', 'limit' => 4, 'noun' => 'detail', 'fields' => [
                        ['key' => 'label', 'label' => 'Label (for example Date)', 'kind' => 'text', 'max' => 30],
                        ['key' => 'value', 'label' => 'Value (for example Wednesday,|5 August 2026)', 'kind' => 'text', 'max' => 100, 'hint' => 'Type | where the line should break.'],
                    ]],
                    ['key' => 'button', 'label' => 'First button', 'kind' => 'select', 'options' => self::BUTTONS],
                    ['key' => 'button2', 'label' => 'Second button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Welcome', 'phrases' => '', 'lead' => '', 'subheading' => '', 'image' => '', 'foot' => '', 'facts' => [], 'button' => 'apply', 'button2' => 'none'],
            ],
            'text' => [
                'label' => 'Text and picture', 'help' => 'A heading, some paragraphs, and an optional picture beside them.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 6000, 'format' => true, 'hint' => 'Leave a blank line between paragraphs. Use the buttons above the box for headings, lists, bold and links.'],
                    ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                    ['key' => 'side', 'label' => 'Picture sits on the', 'kind' => 'select', 'options' => ['right' => 'Right', 'left' => 'Left']],
                    ['key' => 'align', 'label' => 'When there is no picture, line up the text', 'kind' => 'select', 'options' => ['left' => 'To the left', 'center' => 'In the centre']],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                ],
                'default' => ['eyebrow' => '', 'heading' => '', 'body' => '', 'image' => '', 'side' => 'right', 'align' => 'left', 'band' => 'plain'],
            ],
            'features' => [
                'label' => 'Cards', 'help' => 'Up to 8 cards, each with a picture, a title and a few words. Good for programmes, facilities or reasons to choose you.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'style', 'label' => 'Look', 'kind' => 'select', 'options' => ['cards' => 'Cards with pictures or icons', 'pillars' => 'Numbered cards (Pillar I, Pillar II ...)', 'panels' => 'Plain panels with a coloured bar on top (for example Vision and Mission)']],
                    ['key' => 'tag', 'label' => 'Word before each number (numbered cards only)', 'kind' => 'text', 'max' => 20],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'list', 'limit' => 8, 'noun' => 'card', 'fields' => [
                        ['key' => 'icon', 'label' => 'Small picture', 'kind' => 'select', 'options' => self::ICONS],
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A few words about it', 'kind' => 'textarea', 'max' => 300],
                        ['key' => 'image', 'label' => 'Picture (optional)', 'kind' => 'image'],
                        ['key' => 'link', 'label' => 'Button under the card (only the Grove and Leaf themes show it)', 'kind' => 'select', 'options' => self::BUTTONS],
                    ]],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Why families choose us', 'intro' => '', 'style' => 'cards', 'tag' => 'Pillar', 'band' => 'soft', 'items' => []],
            ],
            'actions' => [
                'label' => 'Quick action cards', 'help' => 'Three big cards that sit over the bottom of the top banner, for example Apply online, Sign in and Talk to us.',
                'fields' => [
                    ['key' => 'items', 'label' => 'Cards', 'kind' => 'list', 'limit' => 4, 'noun' => 'card', 'fields' => [
                        ['key' => 'icon', 'label' => 'Small picture', 'kind' => 'select', 'options' => self::ICONS],
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 60],
                        ['key' => 'body', 'label' => 'A few words', 'kind' => 'text', 'max' => 120],
                        ['key' => 'link', 'label' => 'Where it goes', 'kind' => 'select', 'options' => self::BUTTONS],
                    ]],
                ],
                'default' => ['items' => []],
            ],
            'steps' => [
                'label' => 'Steps', 'help' => 'Numbered steps, for example how to join the school.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                    ['key' => 'items', 'label' => 'Steps', 'kind' => 'list', 'limit' => 8, 'noun' => 'step', 'fields' => [
                        ['key' => 'title', 'label' => 'Title', 'kind' => 'text', 'max' => 80],
                        ['key' => 'body', 'label' => 'A few words about it', 'kind' => 'textarea', 'max' => 300],
                    ]],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'How it works', 'intro' => '', 'band' => 'plain', 'items' => []],
            ],
            'stats' => [
                'label' => 'Big numbers', 'help' => 'Up to 4 proud facts, for example 25 Years or 1,200 Students.',
                'fields' => [
                    $heading(),
                    ['key' => 'items', 'label' => 'Numbers', 'kind' => 'list', 'limit' => 4, 'noun' => 'number', 'fields' => [
                        ['key' => 'number', 'label' => 'The number', 'kind' => 'text', 'max' => 14],
                        ['key' => 'label', 'label' => 'What it counts', 'kind' => 'text', 'max' => 40],
                    ]],
                ],
                'default' => ['heading' => '', 'items' => []],
            ],
            'quote' => [
                'label' => 'Quote', 'help' => 'A pulled-out sentence in large italics with a coloured bar beside it, and who said it.',
                'fields' => [
                    ['key' => 'text', 'label' => 'The quote', 'kind' => 'textarea', 'max' => 400],
                    ['key' => 'who', 'label' => 'Who said it', 'kind' => 'text', 'max' => 100],
                ],
                'default' => ['text' => '', 'who' => ''],
            ],
            'testimonials' => [
                'label' => 'Testimonials', 'help' => 'What parents, pupils or old pupils say about the school, up to 8, shown one at a time that visitors swipe through. Only add words people have really said and agreed to share.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'band', 'label' => 'Background', 'kind' => 'select', 'options' => self::BANDS],
                    ['key' => 'items', 'label' => 'Testimonials', 'kind' => 'list', 'limit' => 8, 'noun' => 'testimonial', 'fields' => [
                        ['key' => 'text', 'label' => 'What they said', 'kind' => 'textarea', 'max' => 500],
                        ['key' => 'name', 'label' => 'Name', 'kind' => 'text', 'max' => 80],
                        ['key' => 'role', 'label' => 'Who they are (for example Parent of a Year 3 pupil)', 'kind' => 'text', 'max' => 100],
                        ['key' => 'image', 'label' => 'Photo (optional)', 'kind' => 'image'],
                    ]],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'What families say', 'band' => 'plain', 'items' => []],
            ],
            'profile' => [
                'label' => 'Person profile', 'help' => 'A photo with a coloured panel under it: a name, a role and a few paragraphs. Good for the head teacher, founder or convener.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label (for example The Head Teacher)', 'kind' => 'text', 'max' => 40],
                    ['key' => 'name', 'label' => 'Name', 'kind' => 'text', 'max' => 80],
                    ['key' => 'role', 'label' => 'Role', 'kind' => 'text', 'max' => 60],
                    ['key' => 'body', 'label' => 'About them', 'kind' => 'textarea', 'max' => 3000, 'format' => true],
                    ['key' => 'image', 'label' => 'Photo', 'kind' => 'image', 'hint' => 'A portrait photo works best.'],
                ],
                'default' => ['eyebrow' => '', 'name' => '', 'role' => '', 'body' => '', 'image' => ''],
            ],
            'news' => [
                'label' => 'Latest news', 'help' => 'Shows your newest posts from News and events. It updates by itself. Before you write any posts, it shows announcements meant for everyone.',
                'fields' => [
                    $heading(),
                    ['key' => 'category', 'label' => 'Which posts', 'kind' => 'select', 'options' => ['all' => 'All kinds'] + \App\Models\SchoolSitePost::CATEGORIES],
                    ['key' => 'count', 'label' => 'How many to show (1 to 10)', 'kind' => 'number', 'min' => 1, 'max' => 10],
                ],
                'default' => ['heading' => 'Latest news', 'category' => 'all', 'count' => 3],
            ],
            'gallery' => [
                'label' => 'Photo gallery', 'help' => 'Shows your newest photos from the Gallery. It updates by itself and stays hidden until you have added photos.',
                'fields' => [
                    ['key' => 'eyebrow', 'label' => 'Small label above the heading', 'kind' => 'text', 'max' => 40],
                    $heading(),
                    ['key' => 'count', 'label' => 'How many photos (4 to 12)', 'kind' => 'number', 'min' => 4, 'max' => 12],
                ],
                'default' => ['eyebrow' => '', 'heading' => 'Life at our school', 'count' => 8],
            ],
            'apply' => [
                'label' => 'Call to action', 'help' => 'A bold coloured band that sends visitors to apply or get in touch. Applications land in Admission Inquiries.',
                'fields' => [
                    $heading(),
                    ['key' => 'body', 'label' => 'Text', 'kind' => 'textarea', 'max' => 600],
                    ['key' => 'button', 'label' => 'First button', 'kind' => 'select', 'options' => self::BUTTONS],
                    ['key' => 'button2', 'label' => 'Second button', 'kind' => 'select', 'options' => self::BUTTONS],
                ],
                'default' => ['heading' => 'Join our school', 'body' => 'Admission is open. Fill in a short form and we will contact you.', 'button' => 'apply', 'button2' => 'contact'],
            ],
            'faq' => [
                'label' => 'Questions and answers', 'help' => 'Answer what parents often ask. Google can show these answers in search results.',
                'fields' => [
                    $heading(),
                    ['key' => 'items', 'label' => 'Questions', 'kind' => 'list', 'limit' => 12, 'noun' => 'question', 'fields' => [
                        ['key' => 'question', 'label' => 'Question', 'kind' => 'text', 'max' => 160],
                        ['key' => 'answer', 'label' => 'Answer', 'kind' => 'textarea', 'max' => 800],
                    ]],
                ],
                'default' => ['heading' => 'Questions parents ask', 'items' => []],
            ],
            'live_fees' => [
                'label' => 'Fees by class (live)', 'help' => 'Your fee list by class, read from Fees > Fee structures for the current school year. It updates by itself and stays hidden until fees are set.',
                'fields' => [
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'note', 'label' => 'Note under the list (for example how to pay)', 'kind' => 'textarea', 'max' => 400],
                ],
                'default' => ['heading' => 'School fees', 'intro' => 'Fees for the current school year.', 'note' => 'Parents can pay online from the parent portal, in full or in parts.'],
            ],
            'live_dates' => [
                'label' => 'Term dates and holidays (live)', 'help' => 'Your term start and end dates and coming holidays, read from School Setup. It updates by itself.',
                'fields' => [$heading()],
                'default' => ['heading' => 'Term dates'],
            ],
            'team' => [
                'label' => 'Our team (live)', 'help' => 'The staff you tick "Show on the school website" on their staff page: name, role and photo.',
                'fields' => [$heading(), ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300]],
                'default' => ['heading' => 'Meet our team', 'intro' => ''],
            ],
            'events' => [
                'label' => 'Upcoming events (live)', 'help' => 'Events you publish in News and events, soonest first. Past events drop off by themselves.',
                'fields' => [
                    $heading(),
                    ['key' => 'count', 'label' => 'How many to show (1 to 12)', 'kind' => 'number', 'min' => 1, 'max' => 12],
                ],
                'default' => ['heading' => 'Upcoming events', 'count' => 6],
            ],
            'downloads' => [
                'label' => 'Downloads', 'help' => 'Forms and documents parents can download, such as the prospectus or the admission form. Paste the link to each file (for example from Google Drive).',
                'fields' => [
                    $heading(),
                    ['key' => 'items', 'label' => 'Files', 'kind' => 'list', 'limit' => 12, 'noun' => 'file', 'fields' => [
                        ['key' => 'title', 'label' => 'Name (for example Prospectus 2026)', 'kind' => 'text', 'max' => 100],
                        ['key' => 'link', 'label' => 'Link to the file (starts with https://)', 'kind' => 'text', 'max' => 400],
                    ]],
                ],
                'default' => ['heading' => 'Downloads', 'items' => []],
            ],
            'contact_form' => [
                'label' => 'Message form', 'help' => 'A short form visitors fill in. Messages land in Admissions > Inquiries and staff get a bell notice.',
                'fields' => [
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                ],
                'default' => ['heading' => 'Send us a message', 'intro' => 'Ask anything. Our office will call or email you back.'],
            ],
            'custom_form' => [
                'label' => 'Custom form', 'help' => 'One of the forms you built in Extensions > Forms (a prospectus request, a job application, a survey). Needs the Forms extension switched on and the form kept open.',
                'fields' => [
                    ['key' => 'form', 'label' => 'Which form', 'kind' => 'select', 'options' => [], 'dynamic' => 'forms'],
                    ['key' => 'heading', 'label' => 'Heading (leave empty to use the form name)', 'kind' => 'text', 'max' => 120],
                    ['key' => 'intro', 'label' => 'A sentence under the heading (leave empty to use the form\'s own)', 'kind' => 'text', 'max' => 300],
                ],
                'default' => ['form' => '', 'heading' => '', 'intro' => ''],
            ],
            'newsletter_signup' => [
                'label' => 'Newsletter sign-up', 'help' => 'Visitors give their email and agree to get your newsletter, then confirm from an email we send. Needs the Newsletter extension switched on and an email service set up, otherwise it stays hidden.',
                'fields' => [
                    $heading(),
                    ['key' => 'intro', 'label' => 'A sentence under the heading', 'kind' => 'text', 'max' => 300],
                    ['key' => 'button', 'label' => 'Button words', 'kind' => 'text', 'max' => 30],
                    ['key' => 'interests', 'label' => 'Let people choose what they hear about', 'kind' => 'select', 'options' => ['yes' => 'Yes (news, events, admissions)', 'no' => 'No']],
                ],
                'default' => ['heading' => 'Stay in touch', 'intro' => 'Get news, events and admission updates from our school by email.', 'button' => 'Sign up', 'interests' => 'yes'],
            ],
            'contact' => [
                'label' => 'Contact details', 'help' => 'Your address, phone and email, taken from your school profile.',
                'fields' => [$heading()],
                'default' => ['heading' => 'Find us'],
            ],
        ];
    }

    /** The sections offered in the editor. Sections that belong to an extension are offered only when the school may use it. */
    public static function catalogue(?int $schoolId = null): array
    {
        return collect(self::types())
            ->filter(fn ($t, $key) => $schoolId === null || Extensions::blockAllowed($key, $schoolId))
            ->map(function ($t, $key) use ($schoolId) {
                // a section that picks one of the school's own things (its forms) lists them in the editor
                foreach ($t['fields'] as $i => $f) {
                    if (($f['dynamic'] ?? null) === 'forms') {
                        $own = $schoolId ? SiteForm::withoutGlobalScopes()->where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id')->all() : [];
                        // keys like "f12" (not bare numbers) so the editor keeps "Choose a form" first
                        $t['fields'][$i]['options'] = ['' => 'Choose a form'] + array_combine(array_map(fn ($id) => 'f'.$id, array_keys($own)), array_values($own));
                    }
                }

                return ['type' => $key] + $t;
            })->values()->all();
    }

    public static function fresh(string $type): ?array
    {
        $t = self::types()[$type] ?? null;

        return $t ? ['type' => $type, 'hidden' => false] + $t['default'] : null;
    }

    /**
     * Keep only known section types and known fields, trimmed to their limits, so nothing odd can be stored.
     * Pictures must be ones this school uploaded to its own website folder.
     */
    public static function clean(mixed $blocks, ?int $schoolId = null): array
    {
        $types = self::types();
        $out = [];

        foreach (array_slice(is_array($blocks) ? $blocks : [], 0, self::MAX_BLOCKS) as $block) {
            $type = is_array($block) ? ($block['type'] ?? null) : null;
            if (! is_string($type) || ! isset($types[$type])) {
                continue;
            }
            $out[] = ['type' => $type, 'hidden' => filter_var($block['hidden'] ?? false, FILTER_VALIDATE_BOOLEAN)]
                + self::cleanFields($types[$type]['fields'], $block, $schoolId);
        }

        return $out;
    }

    public static function imagePath(mixed $value, ?int $schoolId): string
    {
        $ok = is_string($value) && $schoolId && preg_match('#^schools/'.$schoolId.'/website/[A-Za-z0-9._-]+$#', $value);

        return $ok ? $value : '';
    }

    private static function cleanFields(array $fields, array $source, ?int $schoolId): array
    {
        $clean = [];

        foreach ($fields as $f) {
            $value = $source[$f['key']] ?? null;

            $clean[$f['key']] = match ($f['kind']) {
                'select' => ($f['dynamic'] ?? null) === 'forms'
                    ? (is_string($value) && preg_match('/^f(\d+)$/', $value, $m) && $schoolId && SiteForm::withoutGlobalScopes()->where('school_id', $schoolId)->whereKey((int) $m[1])->exists() ? 'f'.(int) $m[1] : '')
                    : (array_key_exists((string) $value, $f['options']) ? (string) $value : array_key_first($f['options'])),
                'number' => max($f['min'], min($f['max'], (int) $value ?: $f['min'])),
                'image' => self::imagePath($value, $schoolId),
                'list' => collect(is_array($value) ? array_slice($value, 0, $f['limit']) : [])
                    ->filter(fn ($row) => is_array($row))
                    ->map(fn ($row) => self::cleanFields($f['fields'], $row, $schoolId))
                    ->filter(fn ($row) => collect($row)->contains(fn ($v) => $v !== ''))
                    ->values()->all(),
                default => $f['key'] === 'link' ? self::safeLink($value, $f['max']) : mb_substr(trim(is_scalar($value) ? (string) $value : ''), 0, $f['max']),
            };
        }

        return $clean;
    }

    /** Only an https address is kept, so a download link can never run a script */
    private static function safeLink(mixed $value, int $max): string
    {
        $v = trim(is_scalar($value) ? (string) $value : '');

        return preg_match('#^https://[^\s<>"\']+$#i', $v) ? mb_substr($v, 0, $max) : '';
    }

    /**
     * Sections added by developers: resources/website-blocks/{type}/block.json (label, help, fields, default) and
     * view.blade.php. Files that are not valid are ignored, so a typo never breaks a school's website.
     */
    public static function custom(): array
    {
        if (self::$found !== null) {
            return self::$found;
        }
        $found = [];
        $builtIn = self::builtIn();

        foreach (glob(resource_path('website-blocks/*/block.json')) ?: [] as $file) {
            $type = basename(dirname($file));
            $json = json_decode((string) file_get_contents($file), true);
            if (! preg_match('/^[a-z][a-z0-9_]{1,30}$/', $type) || isset($builtIn[$type]) || ! is_array($json) || ! is_file(dirname($file).'/view.blade.php')) {
                continue;
            }
            $fields = self::cleanDefinition($json['fields'] ?? []);
            $default = [];
            foreach ($fields as $f) {
                $default[$f['key']] = $json['default'][$f['key']] ?? match ($f['kind']) { 'number' => $f['min'], 'select' => array_key_first($f['options']), 'list' => [], default => '' };
            }
            $found[$type] = [
                'label' => mb_substr((string) ($json['label'] ?? ucfirst($type)), 0, 60), 'help' => mb_substr((string) ($json['help'] ?? 'A section added by a developer.'), 0, 300),
                'fields' => $fields, 'default' => $default, 'custom' => true,
            ];
        }

        return self::$found = $found;
    }

    /** Forget what was read from folders (used by tests and after files change) */
    public static function flush(): void
    {
        self::$found = null;
    }

    /** Only the known field kinds and well-formed keys are kept from a developer's block.json */
    private static function cleanDefinition(mixed $fields, int $depth = 0): array
    {
        $out = [];
        foreach (is_array($fields) ? array_slice($fields, 0, 20) : [] as $f) {
            if (! is_array($f) || ! preg_match('/^[a-z][a-z0-9_]{0,30}$/', (string) ($f['key'] ?? '')) || ! in_array($f['kind'] ?? '', ['text', 'textarea', 'select', 'number', 'image', 'list'], true)) {
                continue;
            }
            $def = ['key' => $f['key'], 'label' => mb_substr((string) ($f['label'] ?? $f['key']), 0, 80), 'kind' => $f['kind'], 'max' => max(1, min(6000, (int) ($f['max'] ?? 200)))];
            if (! empty($f['hint']) && is_string($f['hint'])) {
                $def['hint'] = mb_substr($f['hint'], 0, 200);
            }
            if ($f['kind'] === 'select') {
                $options = collect(is_array($f['options'] ?? null) ? $f['options'] : [])->filter(fn ($v) => is_string($v))->take(20)->all();
                if ($options === []) {
                    continue;
                }
                $def['options'] = $options;
            }
            if ($f['kind'] === 'number') {
                $def['min'] = (int) ($f['min'] ?? 0);
                $def['max'] = max($def['min'], (int) ($f['max'] ?? 100));
            }
            if ($f['kind'] === 'list') {
                if ($depth > 0) {
                    continue; // lists inside lists are not supported
                }
                $inner = self::cleanDefinition($f['fields'] ?? [], 1);
                if ($inner === []) {
                    continue;
                }
                $def += ['limit' => max(1, min(20, (int) ($f['limit'] ?? 8))), 'noun' => mb_substr((string) ($f['noun'] ?? 'item'), 0, 20), 'fields' => $inner];
            }
            $out[] = $def;
        }

        return $out;
    }
}
