<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('betting_history', function (Blueprint $table) {
            $table->id();
            $table->string('match');
            $table->string('tip');
            $table->decimal('odds', 8, 2);
            $table->decimal('stake', 10, 2);
            $table->enum('outcome', ['W', 'L', 'P'])->default('P');
            $table->decimal('win_loss', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('betting_history');
    }
}; 