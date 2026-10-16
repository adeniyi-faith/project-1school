<?php

namespace App\Models;

use App\Traits\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A school's public website settings: on/off, theme, colour, tagline */
class SchoolSite extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'enabled', 'theme', 'color', 'color2', 'social', 'options', 'tagline', 'meta_description', 'footer_text'];

    protected $casts = ['enabled' => 'boolean', 'social' => 'array', 'options' => 'array'];

    /** One site option (news menu label, show news in menu ...) with its default */
    public function option(string $key): mixed
    {
        $defaults = ['news_label' => 'News', 'news_in_menu' => true, 'apply_button' => true, 'announcement' => '', 'cookie_notice' => true, 'footer_signup' => false, 'footer_signup_text' => '', 'logo_badge' => 'auto', 'logo_badge_shape' => 'rounded', 'logo_badge_colour' => 'white', 'whatsapp' => '', 'whatsapp_text' => '', 'custom_css' => '', 'ga_id' => '', 'verify_google' => '', 'verify_bing' => ''];

        return ($this->options ?? [])[$key] ?? $defaults[$key] ?? null;
    }
}
