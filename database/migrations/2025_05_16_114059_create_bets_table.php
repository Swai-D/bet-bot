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
        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prediction_id')->constrained()->onDelete('cascade');
            $table->string('betpawa_id')->unique();
            $table->float('amount');
            $table->string('status')->default('pending'); // pending, placed, won, lost
            $table->float('potential_winnings');
            $table->timestamp('placed_at')->nullable();
            $table->json('raw_data')->nullable(); // Store complete bet data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bets');
    }
};
