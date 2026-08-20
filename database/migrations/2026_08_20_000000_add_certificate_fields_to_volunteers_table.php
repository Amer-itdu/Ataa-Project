<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteers', function (Blueprint $table) {
            $table->uuid('certificate_token')->nullable()->unique();
            $table->timestamp('certificate_issued_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('volunteers', function (Blueprint $table) {
            $table->dropUnique(['certificate_token']);
            $table->dropColumn(['certificate_token', 'certificate_issued_at']);
        });
    }
};
