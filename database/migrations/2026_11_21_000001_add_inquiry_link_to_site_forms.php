<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_forms') && ! Schema::hasColumn('site_forms', 'send_to_inquiries')) {
            Schema::table('site_forms', function (Blueprint $t) {
                $t->boolean('send_to_inquiries')->default(false);
            });
        }
        if (Schema::hasTable('site_form_submissions') && ! Schema::hasColumn('site_form_submissions', 'inquiry_id')) {
            Schema::table('site_form_submissions', function (Blueprint $t) {
                $t->unsignedBigInteger('inquiry_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('site_form_submissions', 'inquiry_id')) {
            Schema::table('site_form_submissions', fn (Blueprint $t) => $t->dropColumn('inquiry_id'));
        }
        if (Schema::hasColumn('site_forms', 'send_to_inquiries')) {
            Schema::table('site_forms', fn (Blueprint $t) => $t->dropColumn('send_to_inquiries'));
        }
    }
};
