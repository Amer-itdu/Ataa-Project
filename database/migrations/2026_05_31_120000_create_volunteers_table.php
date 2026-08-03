<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('phone')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('occupation')->nullable();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();

            $table->json('skills')->nullable();
            $table->string('availability')->nullable();
            $table->text('description')->nullable();

            $table->boolean('agreed_to_terms')->default(false);
            $table->timestamp('agreed_to_terms_at')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
             $table->boolean('general_application')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('volunteers');
    }
};