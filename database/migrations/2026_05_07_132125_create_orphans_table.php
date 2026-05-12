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
      Schema::create('orphans', function (Blueprint $table) {
    $table->id();

    // كل يتيم مرتبط بطلب واحد
    $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');

    // ملفات
    $table->string('family_booklet');            // دفتر العائلة
    $table->string('father_death_certificate');  // شهادة وفاة الأب

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orphans');
    }
};
