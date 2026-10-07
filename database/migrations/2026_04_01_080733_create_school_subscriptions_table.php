<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('package_id')->index();
            $table->unsignedBigInteger('coupon_id')->nullable()->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['trial','active','expired','suspended'])->default('trial');
            $table->boolean('is_trial')->default(false);
            $table->date('trial_ends_at')->nullable();
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreign('school_id',  'sub_school_fk')->references('id')->on('schools')->cascadeOnDelete();
            $table->foreign('package_id', 'sub_package_fk')->references('id')->on('packages');
            $table->foreign('coupon_id',  'sub_coupon_fk')->references('id')->on('coupons')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_subscriptions');
    }
};
