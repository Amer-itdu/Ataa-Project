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
      Schema::create('school_students', function (Blueprint $table) {
    $table->id();

    $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');

    $table->string('academic_grade');
    $table->string('school_name');
    $table->string('family_book_photo'); // ملف دفتر العائلة

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_students');
    }
};
