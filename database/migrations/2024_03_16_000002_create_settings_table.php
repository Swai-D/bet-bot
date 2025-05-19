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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('min_odds', 5, 2)->default(2.00);
            $table->integer('auto_select_count')->default(3);
            $table->decimal('bet_amount', 10, 2)->default(1000.00);
            $table->enum('selection_mode', ['auto', 'manual'])->default('manual');
            $table->boolean('auto_run_scraper')->default(false);
            $table->time('scraper_time')->default('09:00:00');
            $table->boolean('auto_place_bets')->default(false);
            $table->string('confidence_threshold')->default('medium');
            $table->json('bet_types')->default(json_encode([
                'homeWin' => true,
                'draw' => true,
                'awayWin' => true,
                'over2_5' => true
            ]));
            $table->boolean('enable_notifications')->default(false);
            $table->timestamp('last_run')->nullable();
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
}; 