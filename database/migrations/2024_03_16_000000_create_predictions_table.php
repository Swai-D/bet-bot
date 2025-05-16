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
            $table->string('match');
            $table->string('country');
            $table->date('date');
            $table->json('tips');
            $table->json('raw_data')->nullable();
            $table->boolean('selected')->default(false);
            $table->timestamps();

            // Add indexes for better query performance
            $table->index('date');
            $table->index('country');
            $table->index('selected');
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