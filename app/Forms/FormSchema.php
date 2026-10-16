<?php

namespace App\Forms;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * What a school's custom form is made of, and how a visitor's post is checked against it.
 * Everything the school types is cleaned here before it is saved, and everything a visitor posts is checked here
 * before it is stored, so the form can only ever receive what the school asked for.
 */
class FormSchema
{
    public const TYPES = [
        'text' => 'Short answer', 'textarea' => 'Long answer', 'email' => 'Email address', 'phone' => 'Phone number', 'number' => 'Number',
        'date' => 'Date', 'select' => 'Drop-down list', 'radio' => 'Pick one', 'checkboxes' => 'Tick any that apply', 'tick' => 'Single tick box',
        'file' => 'File upload',
    ];

    public const CHOICES = ['select', 'radio', 'checkboxes'];

    /** Questions another question can depend on: the ones with a clear "this answer was picked" */
    public const CONTROLS = ['select', 'radio', 'checkboxes', 'tick'];

    public const MAX_FIELDS = 20;
    public const MAX_OPTIONS = 20;
    public const MAX_CONSENTS = 5;
    public const MAX_FILES = 3;

    /** Uploads: what is accepted and how big. Pictures and documents only, never anything a browser could run. */
    public const FILE_TYPES = 'pdf,doc,docx,jpg,jpeg,png';
    public const FILE_KB = 5120;

    /** Where an answer goes in an Admissions inquiry, and which kinds of question can fill it */
    public const MAPS = [
        'student_name' => ['label' => "Child's name", 'types' => ['text']],
        'class_interested' => ['label' => 'Class wanted', 'types' => ['text', 'select', 'radio']],
        'student_gender' => ['label' => "Child's gender (male, female or other)", 'types' => ['select', 'radio', 'text']],
        'student_dob' => ['label' => "Child's date of birth", 'types' => ['date']],
        'guardian_name' => ['label' => "Parent's name", 'types' => ['text']],
        'guardian_phone' => ['label' => "Parent's phone number", 'types' => ['phone', 'text']],
        'guardian_email' => ['label' => "Parent's email", 'types' => ['email']],
    ];

    /** Tidy the fields a school typed: known types only, trimmed, a stable key for each */
    public static function cleanFields(mixed $fields): array
    {
        $out = [];
        $seen = [];
        $usedMaps = [];
        foreach (array_slice(is_array($fields) ? $fields : [], 0, self::MAX_FIELDS) as $f) {
            if (! is_array($f)) {
                continue;
            }
            $label = mb_substr(trim((string) ($f['label'] ?? '')), 0, 120);
            $type = (string) ($f['type'] ?? '');
            if ($label === '' || ! isset(self::TYPES[$type])) {
                continue;
            }
            $key = (string) ($f['key'] ?? '');
            if (! preg_match('/^f[a-z0-9]{4,10}$/', $key) || isset($seen[$key])) {
                $key = 'f'.Str::lower(Str::random(6));
            }
            $seen[$key] = true;
            $options = [];
            if (in_array($type, self::CHOICES, true)) {
                $options = collect(is_array($f['options'] ?? null) ? $f['options'] : preg_split('/\R/', (string) ($f['options'] ?? '')))
                    ->map(fn ($o) => mb_substr(trim((string) $o), 0, 100))->filter(fn ($o) => $o !== '')->unique()->take(self::MAX_OPTIONS)->values()->all();
            }
            $map = (string) ($f['maps_to'] ?? '');
            if (! isset(self::MAPS[$map]) || ! in_array($type, self::MAPS[$map]['types'], true) || isset($usedMaps[$map])) {
                $map = '';
            }
            if ($map !== '') {
                $usedMaps[$map] = true;
            }
            $showIf = is_array($f['show_if'] ?? null) ? ['key' => (string) ($f['show_if']['key'] ?? ''), 'value' => mb_substr(trim((string) ($f['show_if']['value'] ?? '')), 0, 100)] : null;
            if ($showIf && $showIf['key'] === '') {
                $showIf = null;
            }
            $out[] = [
                'key' => $key, 'label' => $label, 'type' => $type, 'required' => filter_var($f['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'help' => mb_substr(trim((string) ($f['help'] ?? '')), 0, 200), 'placeholder' => in_array($type, ['text', 'textarea', 'email', 'phone', 'number'], true) ? mb_substr(trim((string) ($f['placeholder'] ?? '')), 0, 80) : '',
                'options' => $options, 'half' => filter_var($f['half'] ?? false, FILTER_VALIDATE_BOOLEAN) && ! in_array($type, ['textarea', 'checkboxes'], true),
                'show_if' => $showIf, 'maps_to' => $map,
            ];
        }

        return $out;
    }

    /** The privacy ticks: wording the visitor agrees to, and whether the form can be sent without it */
    public static function cleanConsents(mixed $consents): array
    {
        return collect(is_array($consents) ? array_slice($consents, 0, self::MAX_CONSENTS) : [])
            ->filter(fn ($c) => is_array($c))
            ->map(fn ($c) => ['text' => mb_substr(trim((string) ($c['text'] ?? '')), 0, 300), 'required' => filter_var($c['required'] ?? false, FILTER_VALIDATE_BOOLEAN)])
            ->filter(fn ($c) => $c['text'] !== '')->values()->all();
    }

    /** A plain sentence about what is wrong with the fields, or null when they are fine */
    public static function problem(array $fields, bool $sendToInquiries = false): ?string
    {
        if ($fields === []) {
            return 'Add at least one question.';
        }
        if (collect($fields)->where('type', 'file')->count() > self::MAX_FILES) {
            return 'A form can have at most '.self::MAX_FILES.' file upload questions.';
        }
        $before = [];
        foreach ($fields as $f) {
            if (in_array($f['type'], self::CHOICES, true) && count($f['options']) < 2) {
                return '"'.$f['label'].'" needs at least two choices.';
            }
            if ($f['show_if']) {
                $c = $before[$f['show_if']['key']] ?? null;
                if (! $c || ! in_array($c['type'], self::CONTROLS, true)) {
                    return '"'.$f['label'].'" is set to appear after another question, but that question is missing or comes later. Move it below that question or remove the condition.';
                }
                if ($c['type'] !== 'tick' && ! in_array($f['show_if']['value'], $c['options'], true)) {
                    return '"'.$f['label'].'" waits for an answer that "'.$c['label'].'" does not offer. Pick one of its choices.';
                }
            }
            $before[$f['key']] = $f;
        }
        if ($sendToInquiries) {
            $maps = collect($fields)->pluck('maps_to')->filter()->all();
            if (! in_array('guardian_name', $maps, true) || ! in_array('guardian_phone', $maps, true)) {
                return 'To add answers to Admissions, say which question is the parent\'s name and which is the parent\'s phone number.';
            }
        }

        return null;
    }

    /**
     * The questions this visitor should have seen, given what they answered. A question with a condition shows only when the
     * question it depends on has the chosen answer, and a question that depends on a hidden one is hidden too.
     *
     * @return array<string, true> keys of the questions in play
     */
    public static function visible(array $fields, array $posted): array
    {
        $shown = [];
        foreach ($fields as $f) {
            $c = $f['show_if'] ?? null;
            if ($c) {
                if (! isset($shown[$c['key']])) {
                    continue;
                }
                $answer = $posted[$c['key']] ?? null;
                $control = collect($fields)->firstWhere('key', $c['key']);
                $match = ($control['type'] ?? '') === 'tick' ? ! empty($answer) : (in_array($control['type'] ?? '', ['checkboxes'], true) ? in_array($c['value'], (array) $answer, true) : $answer === $c['value']);
                if (! $match) {
                    continue;
                }
            }
            $shown[$f['key']] = true;
        }

        return $shown;
    }

    /** Laravel validation rules for a visitor's post: answers under `a[key]`, privacy ticks under `c[number]`. Hidden questions get none. */
    public static function rules(array $fields, array $consents, ?array $visible = null): array
    {
        $rules = [];
        foreach ($fields as $f) {
            if ($visible !== null && ! isset($visible[$f['key']])) {
                continue;
            }
            $k = 'a.'.$f['key'];
            $need = $f['required'] ? 'required' : 'nullable';
            $rules[$k] = match ($f['type']) {
                'text' => [$need, 'string', 'max:200'],
                'textarea' => [$need, 'string', 'max:2000'],
                'email' => [$need, 'email:rfc', 'max:150'],
                'phone' => [$need, 'string', 'regex:/^[0-9+\s().-]{6,25}$/'],
                'number' => [$need, 'numeric', 'between:-1000000000,1000000000'],
                'date' => [$need, 'date_format:Y-m-d'],
                'select', 'radio' => [$need, 'string', Rule::in($f['options'])],
                'checkboxes' => [$f['required'] ? 'required' : 'nullable', 'array', 'max:'.self::MAX_OPTIONS],
                'tick' => [$f['required'] ? 'accepted' : 'nullable', 'boolean'],
                'file' => [$need, 'file', 'mimes:'.self::FILE_TYPES, 'max:'.self::FILE_KB],
            };
            if ($f['type'] === 'checkboxes') {
                $rules[$k.'.*'] = ['string', Rule::in($f['options'])];
            }
        }
        foreach ($consents as $i => $c) {
            $rules['c.'.$i] = [$c['required'] ? 'accepted' : 'nullable', 'boolean'];
        }

        return $rules;
    }

    public static function attributeNames(array $fields, array $consents): array
    {
        $names = [];
        foreach ($fields as $f) {
            $names['a.'.$f['key']] = '"'.$f['label'].'"';
        }
        foreach ($consents as $i => $c) {
            $names['c.'.$i] = 'the privacy box "'.Str::limit($c['text'], 40).'"';
        }

        return $names;
    }

    /**
     * The answers as they were given, with the question wording copied in: [{key, label, type, value}].
     * Questions the visitor did not see are left out. A file answer also carries `file` (where it was stored) and `size`; `$storeFile` stores it.
     *
     * @param callable(UploadedFile, string): array{path: string, name: string, size: int}|null $storeFile
     */
    public static function answers(array $fields, array $posted, ?array $visible = null, ?callable $storeFile = null): array
    {
        $out = [];
        foreach ($fields as $f) {
            if ($visible !== null && ! isset($visible[$f['key']])) {
                continue;
            }
            $v = $posted[$f['key']] ?? null;
            $entry = ['key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'], 'value' => ''];
            if ($f['type'] === 'file') {
                if ($v instanceof UploadedFile && $storeFile) {
                    $stored = $storeFile($v, $f['key']);
                    $entry['value'] = $stored['name'];
                    $entry['file'] = $stored['path'];
                    $entry['size'] = $stored['size'];
                }
            } else {
                $entry['value'] = match ($f['type']) {
                    'tick' => $v ? 'Yes' : 'No',
                    'checkboxes' => implode(', ', array_values(array_intersect($f['options'], (array) $v))),
                    default => is_scalar($v) ? trim((string) $v) : '',
                };
            }
            $out[] = $entry;
        }

        return $out;
    }

    /** The privacy ticks as they were answered, with the wording the visitor saw */
    public static function consentsGiven(array $consents, array $posted): array
    {
        return collect($consents)->map(fn ($c, $i) => ['text' => $c['text'], 'required' => $c['required'], 'agreed' => ! empty($posted[$i])])->all();
    }
}
