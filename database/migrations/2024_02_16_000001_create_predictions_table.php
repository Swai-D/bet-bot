<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->string('match_id')->unique();
            $table->string('match');
            $table->string('country');
            $table->string('league')->default('Unknown League');
            $table->dateTime('date');
            $table->float('score')->default(0);
            $table->json('tips')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('predictions');
    }
}; 