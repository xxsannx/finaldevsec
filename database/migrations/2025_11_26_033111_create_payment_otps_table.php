<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_otp', 6)->nullable()->after('otp_verified');
            $table->timestamp('payment_otp_expires_at')->nullable()->after('payment_otp');
            $table->boolean('payment_verified')->default(false)->after('payment_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['payment_otp', 'payment_otp_expires_at', 'payment_verified']);
        });
    }
};