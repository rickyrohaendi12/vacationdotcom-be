<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_class_id')->constrained('schedule_classes')->cascadeOnDelete();
            $table->string('seat_number', 10); // "12A"
            $table->enum('status', ['available', 'locked', 'booked'])->default('available');
            $table->timestamp('locked_until')->nullable(); // untuk seat locking sementara
            $table->timestamps();

            $table->unique(['schedule_class_id', 'seat_number']);
            $table->index('status'); // sering difilter saat cek ketersediaan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
