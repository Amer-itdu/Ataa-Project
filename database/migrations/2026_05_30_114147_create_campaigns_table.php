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

            // منشئ الحملة (admin أو sub_admin)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // معلومات الحملة
            $table->string('title');
            $table->text('description')->nullable();

            // نوع الحملة
            $table->enum('type', [
                'educational',
                'medical',
                'humanitarian',
                'environmental'
            ]);

            // التبرعات
            $table->decimal('amount_needed', 12, 2)->nullable();
            $table->decimal('amount_collected', 12, 2)->default(0);

            // المتطوعين
            $table->integer('volunteers_needed')->nullable();
            $table->integer('volunteers_joined')->default(0);

            // حالة الحملة
            $table->enum('status', [
                'open',                 // مفتوحة
                'closed',               // مغلقة يدويًا
                'completed_donations',  // اكتملت التبرعات
                'completed_volunteers', // اكتمل عدد المتطوعين
                'completed_all',        // اكتمل كل شيء
                'expired',              // انتهى الوقت
                'paused',               // متوقفة مؤقتًا
                'cancelled'             // ملغاة
            ])->default('open');

            // التواريخ
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
        });

        // جدول صور الحملة
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
