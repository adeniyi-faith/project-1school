<?php

namespace App\Forms;

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
    ];

    public const CHOICES = ['select', 'radio', 'checkboxes'];

    public const MAX_FIELDS = 20;
    public const MAX_OPTIONS = 20;
    public const MAX_CONSENTS = 5;

    /** Tidy the fields a school typed: known types only, trimmed, a stable key for each */
    public static function cleanFields(mixed $fields): array
    {
        $out = [];
        $seen = [];
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
            $out[] = [
                'key' => $key, 'label' => $label, 'type' => $type, 'required' => filter_var($f['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'help' => mb_substr(trim((string) ($f['help'] ?? '')), 0, 200), 'placeholder' => in_array($type, ['text', 'textarea', 'email', 'phone', 'number'], true) ? mb_substr(trim((string) ($f['placeholder'] ?? '')), 0, 80) : '',
                'options' => $options, 'half' => filter_var($f['half'] ?? false, FILTER_VALIDATE_BOOLEAN) && ! in_array($type, ['textarea', 'checkboxes'], true),
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
    public static function problem(array $fields): ?string
    {
        if ($fields === []) {
            return 'Add at least one question.';
        }
        foreach ($fields as $f) {
            if (in_array($f['type'], self::CHOICES, true) && count($f['options']) < 2) {
                return '"'.$f['label'].'" needs at least two choices.';
            }
        }

        return null;
    }

    /** Laravel validation rules for a visitor's post: answers under `a[key]`, privacy ticks under `c[number]` */
    public static function rules(array $fields, array $consents): array
    {
        $rules = [];
        foreach ($fields as $f) {
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

    /** The answers as they were given, with the question wording copied in: [{key, label, type, value}] */
    public static function answers(array $fields, array $posted): array
    {
        $out = [];
        foreach ($fields as $f) {
            $v = $posted[$f['key']] ?? null;
            $value = match ($f['type']) {
                'tick' => $v ? 'Yes' : 'No',
                'checkboxes' => implode(', ', array_values(array_intersect($f['options'], (array) $v))),
                default => is_scalar($v) ? trim((string) $v) : '',
            };
            $out[] = ['key' => $f['key'], 'label' => $f['label'], 'type' => $f['type'], 'value' => $value];
        }

        return $out;
    }

    /** The privacy ticks as they were answered, with the wording the visitor saw */
    public static function consentsGiven(array $consents, array $posted): array
    {
        return collect($consents)->map(fn ($c, $i) => ['text' => $c['text'], 'required' => $c['required'], 'agreed' => ! empty($posted[$i])])->all();
    }
}
