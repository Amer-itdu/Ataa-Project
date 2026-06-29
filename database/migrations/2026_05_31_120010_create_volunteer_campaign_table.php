<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('volunteer_campaign', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->constrained('volunteers')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->date('assigned_date')->nullable();
            $table->string('status')->default('pending');

            // 🔥 بيانات خاصة بهذا التطوع المحدد لهذه الحملة
            $table->string('available_time')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // منع تطوع مكرر لنفس الحملة
            $table->unique(['volunteer_id', 'campaign_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('volunteer_campaign');
    }
};