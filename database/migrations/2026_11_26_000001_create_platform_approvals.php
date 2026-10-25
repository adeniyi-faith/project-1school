<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('platform_approvals')) {
            return;
        }

        // A platform staff member asks for a risky action to be allowed; a second person says yes or no.
        Schema::create('platform_approvals', function (Blueprint $table) {
            $table->id();
            $table->string('action', 120)->index();          // route name, e.g. super-admin.schools.destroy
            $table->string('label', 255);                     // "Deleted a school", copied at the time
            $table->string('target_type', 60)->nullable();    // school, user, package ...
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name', 255)->nullable();
            $table->text('reason');
            $table->unsignedBigInteger('requested_by')->index();
            $table->string('requested_by_name', 255)->nullable();
            // pending, approved, denied, used
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->string('decided_by_name', 255)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->timestamp('expires_at')->nullable();      // an approval can be used until this time
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_approvals');
    }
};
