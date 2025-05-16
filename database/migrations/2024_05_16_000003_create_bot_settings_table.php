<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_odds', 8, 2)->default(2.00);
            $table->integer('auto_select_count')->default(3);
            $table->decimal('bet_amount', 10, 2)->default(1000);
            $table->enum('selection_mode', ['auto', 'manual'])->default('manual');
            $table->boolean('auto_run_scraper')->default(false);
            $table->time('scraper_time')->default('09:00:00');
            $table->boolean('auto_place_bets')->default(false);
            $table->boolean('enable_notifications')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_settings');
    }
}; 