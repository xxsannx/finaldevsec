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
        Schema::table('users', function (Blueprint $table) {
            $table->string('login_otp', 6)->nullable()->after('password');
            $table->timestamp('login_otp_expires_at')->nullable()->after('login_otp');
            $table->boolean('is_login_verified')->default(false)->after('login_otp_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['login_otp', 'login_otp_expires_at', 'is_login_verified']);
        });
    }
};