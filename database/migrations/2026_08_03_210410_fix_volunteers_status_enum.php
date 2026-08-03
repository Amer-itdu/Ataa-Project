<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE volunteers MODIFY status ENUM('active','inactive','suspended','pending','approved','rejected') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE volunteers MODIFY status ENUM('active','inactive','suspended') DEFAULT 'active'");
    }
};