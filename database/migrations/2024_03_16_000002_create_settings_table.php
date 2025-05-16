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
            $table->string('selection_mode')->default('manual');
            $table->boolean('auto_run_scraper')->default(false);
            $table->string('scraper_time')->default('09:00');
            $table->boolean('auto_place_bets')->default(false);
            $table->boolean('enable_notifications')->default(false);
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