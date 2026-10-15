<?php

use App\Models\School;
use App\Models\SchoolSite;
use App\Website\SiteStarter;
use Illuminate\Database\Migrations\Migration;

/** Gives every school that already has a website a Privacy Policy page (not in the menu). A page a school already made with that address is left alone. */
return new class extends Migration
{
    public function up(): void
    {
        $ids = SchoolSite::withoutGlobalScopes()->pluck('school_id');
        School::withoutGlobalScopes()->whereIn('id', $ids)->get()->each(fn (School $s) => SiteStarter::ensurePrivacyPage($s));
    }

    public function down(): void
    {
    }
};
