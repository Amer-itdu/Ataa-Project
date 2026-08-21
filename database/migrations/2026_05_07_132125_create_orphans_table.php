<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orphans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('requests')->onDelete('cascade');
            $table->string('family_booklet');
            $table->string('father_death_certificate');
            
            // Sponsorship columns
            $table->boolean('is_sponsored')->default(false)->comment('Is orphan sponsored');
            $table->foreignId('sponsor_id')->nullable()->constrained('users')->onDelete('set null')->comment('Sponsor user ID');
            $table->decimal('sponsorship_amount', 10, 2)->nullable()->comment('Monthly sponsorship amount');
            $table->timestamp('sponsored_at')->nullable()->comment('Sponsorship start date');
            $table->timestamp('next_monthly_deduction_at')->nullable()->comment('Next monthly deduction date');
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_sponsored');
            $table->index('sponsor_id');
            $table->index('next_monthly_deduction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orphans');
    }
};