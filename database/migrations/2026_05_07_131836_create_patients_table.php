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
       Schema::create('patients', function (Blueprint $table) {
    $table->id();

    $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');

    $table->string('medical_condition');
    $table->decimal('required_amount', 10, 2);

    $table->string('medical_report');     // ملف
    $table->string('national_id_photo');  // ملف

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
