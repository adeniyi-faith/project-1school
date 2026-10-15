<?php

use App\Models\School;
use App\Models\SchoolSite;
use App\Website\SiteStarter;
use Illuminate\Database\Migrations\Migration;

/** Gives the wider starter text (pre-school to senior, day and boarding, faith) to starter pages nobody has edited. Edited pages are not touched. */
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
