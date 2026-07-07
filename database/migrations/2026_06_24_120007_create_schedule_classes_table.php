<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('transport_class_id')->constrained('transport_classes')->cascadeOnDelete();
            $table->decimal('price', 12, 2); // bisa beda dari base_price di schedules
            $table->integer('seat_count'); // total kursi kelas ini
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_classes');
    }
};
