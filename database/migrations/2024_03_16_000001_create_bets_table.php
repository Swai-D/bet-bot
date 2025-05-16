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
            $table->decimal('stake', 10, 2);
            $table->decimal('odds', 5, 2);
            $table->enum('outcome', ['W', 'L', 'P'])->default('P'); // W = Win, L = Loss, P = Pending
            $table->decimal('win_loss', 10, 2)->default(0);
            $table->timestamp('placed_at');
            $table->timestamps();

            // Add indexes for better query performance
            $table->index('outcome');
            $table->index('placed_at');
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