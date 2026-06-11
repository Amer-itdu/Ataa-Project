<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // معلومات المستخدم الأساسية
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();

            // تسجيل الدخول
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');

            // بيانات إضافية
            $table->string('address')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('national_id')->nullable();
            $table->string('international_passport')->nullable();

            // صلاحيات النظام 
            $table->enum('role', ['admin', 'sub_admin', 'field_worker', 'user'])->default('user');
            // نوع المستخدم
            $table->enum('user_category', ['public', 'beneficiary'])->default('public');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            // 🔥 محفظة متعددة العملات (JSON → LONGTEXT في MariaDB)

            $table->json('balances')->nullable();
            // Laravel defaults
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
