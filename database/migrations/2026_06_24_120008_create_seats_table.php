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
            $table->string('seat_number', 10);
            $table->string('status', 20)->default('available'); // available, locked, booked
            $table->timestamp('locked_until')->nullable();
            $table->timestamps();

            $table->unique(['schedule_class_id', 'seat_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
