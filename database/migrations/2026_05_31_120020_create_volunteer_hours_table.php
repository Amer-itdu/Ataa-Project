<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('volunteer_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();

            // 🔥 ربط الساعات بحملة معينة (مفيد لمعرفة وين تطوع الشخص بالضبط)
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();

            $table->date('date');
            $table->decimal('hours', 5, 2)->default(0);
            $table->text('activity_description')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('volunteer_hours');
    }
};