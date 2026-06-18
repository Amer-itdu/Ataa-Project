<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();

            // donor_id بدل user_id
            $table->foreignId('donor_id')->constrained()->onDelete('cascade');

            // polymorphic relation
            $table->unsignedBigInteger('donationable_id');
            $table->string('donationable_type');

            // المبلغ بعد التحويل إلى دولار
            $table->decimal('amount', 12, 2);

            // المبلغ الأصلي قبل التحويل
            $table->decimal('original_amount', 12, 2)->nullable();

            // العملة الأصلية (SAR, AED, SYP, EGP, EUR, USD)
            $table->string('original_currency', 10)->nullable();

            // العملة بعد التحويل (دائمًا USD)
            $table->string('currency', 10)->default('USD');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
