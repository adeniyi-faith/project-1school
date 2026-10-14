<?php

use App\Models\School;
use App\Models\SchoolSite;
use App\Website\SiteStarter;
use Illuminate\Database\Migrations\Migration;

/** Gives schools whose starter pages were never edited the richer starter text. Edited pages are not touched. */
return new class extends Migration
{
    public function up(): void
    {
        $ids = SchoolSite::withoutGlobalScopes()->pluck('school_id');
        School::withoutGlobalScopes()->whereIn('id', $ids)->get()->each(fn (School $s) => SiteStarter::refreshUntouched($s));
    }

    public function down(): void
    {
    }
};
