<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
            $table->decimal('base_price', 12, 2); // harga dasar sebelum kelas
            $table->integer('total_seats');
            $table->timestamps();

            $table->index('departure_time'); // sering dipakai untuk search by tanggal
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
