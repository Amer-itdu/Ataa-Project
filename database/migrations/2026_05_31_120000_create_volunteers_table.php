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

            $table->text('skills')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('volunteers');
    }
};