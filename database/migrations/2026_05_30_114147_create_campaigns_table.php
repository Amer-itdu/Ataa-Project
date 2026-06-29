<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('type', [
                'educational',
                'medical',
                'humanitarian',
                'environmental'
            ]);

            // 🔥 بدون after() — الترتيب هون طبيعي
            $table->enum('participation_type', [
                'donation_only',
                'volunteer_only',
                'donation_and_volunteer',
            ])->default('donation_only');

            $table->decimal('amount_needed', 12, 2)->nullable();
            $table->decimal('amount_collected', 12, 2)->default(0);

            $table->integer('volunteers_needed')->nullable();
            $table->integer('volunteers_joined')->default(0);

            $table->enum('status', [
                'open',
                'closed',
                'completed_donations',
                'completed_volunteers',
                'completed_all',
                'expired',
                'paused',
                'cancelled'
            ])->default('open');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
        });

        Schema::create('campaign_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_media');
        Schema::dropIfExists('campaigns');
    }
};