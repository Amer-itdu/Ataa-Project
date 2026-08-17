<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
         Schema::create('disbursement_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->string('type'); // campaign, request
            $table->unsignedBigInteger('reference_id');
            $table->string('campaign_title')->nullable();
            $table->string('request_title')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['amount_collected', 'is_disbursed']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['amount_collected', 'is_disbursed']);
        });

        Schema::dropIfExists('disbursement_logs');
    }
};