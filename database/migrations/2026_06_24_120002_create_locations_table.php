<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['airport', 'train_station']);
            $table->string('code', 10); // kode IATA (CGK) atau kode stasiun (GMR)
            $table->string('name'); // "Soekarno-Hatta", "Gambir"
            $table->string('city');
            $table->string('country')->default('Indonesia');
            $table->timestamps();

            $table->unique(['type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
