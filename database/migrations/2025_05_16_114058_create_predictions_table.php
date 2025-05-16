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
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->string('match_id')->unique();
            $table->date('date');
            $table->string('country');
            $table->string('team_home');
            $table->string('team_away');
            $table->json('tips');
            $table->json('raw_data')->nullable();
            $table->timestamps();

            // Add index for date to improve query performance
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
