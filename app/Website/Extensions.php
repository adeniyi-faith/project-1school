<?php

namespace App\Website;

use App\Models\PlatformSetting;
use App\Models\SchoolSetting;
use App\Models\SchoolSubscription;

/**
 * The extensions a school can switch on for its website (newsletter, and later forms, a better gallery and so on).
 *
 * An extension is usable by a school when three things are true: it is built (`ready`), the platform owner has not
 * hidden it from the school's plan, and the school has switched it on. Sections that belong to an extension stay
 * saved on a page when it is switched off; they are only hidden from the public site and from the "add a section" list.
 */
class Extensions
{
    /** school_settings key holding the extensions a school switched on (a JSON list of keys) */
    public const SETTING = 'extensions_on';

    /** platform_settings key holding which plans each extension is hidden from: {"newsletter": [3, 5]} */
    public const HIDDEN = 'extensions_hidden';

    /**
     * `ready` false means it is on the roadmap but not built yet (shown as "Coming soon", cannot be switched on).
     * `needs` is shown on the card: an outside account the school must set up, or the AI.
     */
    /** Extra extensions, added only by tests so they can try a working one */
    public static array $extra = [];

    public static function all(): array
    {
        return self::$extra + self::registry();
    }

    private static function registry(): array
    {
        return [
            'newsletter' => [
                'name' => 'Newsletter', 'ready' => true, 'needs' => 'An email service account (Brevo is recommended, or any mailbox that supports SMTP)',
                'description' => 'Visitors sign up on your website and confirm their email. You write a newsletter and send it to everyone who agreed, with an unsubscribe link in every email.',
                'blocks' => ['newsletter_signup'],
            ],
            'forms' => [
                'name' => 'Forms and surveys', 'ready' => false, 'needs' => null,
                'description' => 'Build your own forms (prospectus request, job application, parent survey) and see the answers in one place.',
                'blocks' => [],
            ],
            'gallery_plus' => [
                'name' => 'Advanced gallery', 'ready' => false, 'needs' => null,
                'description' => 'More picture layouts, a full-screen viewer you can swipe through, and filters such as Sports Day or Graduation.',
                'blocks' => [],
            ],
            'testimonials_plus' => [
                'name' => 'Testimonials library', 'ready' => false, 'needs' => null,
                'description' => 'Parents send their own words and a photo, you approve them, and they show on the site in the layout you choose.',
                'blocks' => [],
            ],
            'site_builder' => [
                'name' => 'AI website builder', 'ready' => false, 'needs' => 'AI (uses your school\'s monthly AI allowance)',
                'description' => 'Answer a few questions and get draft pages written for you to check and publish.',
                'blocks' => [],
            ],
            'chat_widget' => [
                'name' => 'AI chat assistant', 'ready' => false, 'needs' => 'AI (uses your school\'s monthly AI allowance)',
                'description' => 'A chat bubble that answers visitors from your own website pages, and passes a message to the office when it does not know.',
                'blocks' => [],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Extension keys the school switched on (whatever the plan allows is checked separately) */
    public static function switchedOn(int $schoolId): array
    {
        $raw = SchoolSetting::get($schoolId, self::SETTING);
        $list = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    public static function set(int $schoolId, string $key, bool $on): void
    {
        if (! self::find($key)) {
            return;
        }
        $list = array_values(array_diff(self::switchedOn($schoolId), [$key]));
        if ($on) {
            $list[] = $key;
        }
        SchoolSetting::set($schoolId, self::SETTING, json_encode(array_values(array_unique($list))), 'website');
    }

    /** Which plans each extension is hidden from, as {key: [packageId, ...]} */
    public static function hiddenMap(): array
    {
        $raw = PlatformSetting::get(self::HIDDEN);
        $map = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($map) ? array_map(fn ($ids) => array_values(array_map('intval', (array) $ids)), $map) : [];
    }

    public static function saveHiddenMap(array $map): void
    {
        $clean = [];
        foreach (array_keys(self::all()) as $key) {
            $ids = array_values(array_unique(array_map('intval', (array) ($map[$key] ?? []))));
            if ($ids !== []) {
                $clean[$key] = $ids;
            }
        }
        PlatformSetting::set(self::HIDDEN, json_encode($clean), 'website');
    }

    /** The plan the school is on now, or null when it has none (then nothing is hidden from it) */
    public static function packageId(int $schoolId): ?int
    {
        $id = SchoolSubscription::where('school_id', $schoolId)->whereIn('status', ['active', 'trial'])->latest('id')->value('package_id');

        return $id ? (int) $id : null;
    }

    public static function hiddenByPlan(int $schoolId, string $key): bool
    {
        $package = self::packageId($schoolId);

        return $package !== null && in_array($package, self::hiddenMap()[$key] ?? [], true);
    }

    /** True only when it is built, allowed on the school's plan and switched on by the school */
    public static function enabled(int $schoolId, string $key): bool
    {
        $ext = self::find($key);

        return $ext && $ext['ready'] && ! self::hiddenByPlan($schoolId, $key) && in_array($key, self::switchedOn($schoolId), true);
    }

    /** The extension a section type belongs to, or null for a section every school has */
    public static function ownerOf(string $blockType): ?string
    {
        foreach (self::all() as $key => $ext) {
            if (in_array($blockType, $ext['blocks'], true)) {
                return $key;
            }
        }

        return null;
    }

    /** May this school use this section type (on the site and in the editor)? */
    public static function blockAllowed(string $blockType, int $schoolId): bool
    {
        $owner = self::ownerOf($blockType);

        return $owner === null || self::enabled($schoolId, $owner);
    }
}
