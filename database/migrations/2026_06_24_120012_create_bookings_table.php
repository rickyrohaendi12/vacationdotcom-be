<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('booking_code')->unique(); // "TRX-ABC123"
            $table->enum('status', ['pending', 'paid', 'cancelled', 'expired'])->default('pending');
            $table->decimal('total_amount', 14, 2);
            $table->timestamp('expires_at'); // untuk auto-cancel kalau tidak dibayar
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
