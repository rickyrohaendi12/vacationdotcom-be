<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operator_id')->constrained('operators')->cascadeOnDelete();
            $table->string('name'); // "Economy", "Business"
            $table->json('facilities')->nullable(); // ["AC", "Snack", "Extra legroom"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_classes');
    }
};
