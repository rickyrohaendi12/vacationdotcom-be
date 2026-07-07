<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->text('address');
            $table->tinyInteger('star_rating'); // 1-5
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();

            $table->index('city'); // sering dipakai untuk search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotels');
    }
};
