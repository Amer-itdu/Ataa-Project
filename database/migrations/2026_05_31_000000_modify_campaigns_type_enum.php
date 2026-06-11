<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new enum values used by the app
        DB::statement("ALTER TABLE `campaigns` MODIFY `type` ENUM('educational','medical','humanitarian','environmental') NOT NULL");
    }

    public function down(): void
    {
        // Revert back to the previous enum values (if needed)
        DB::statement("ALTER TABLE `campaigns` MODIFY `type` ENUM('donation','volunteering','mixed') NOT NULL");
    }
};
