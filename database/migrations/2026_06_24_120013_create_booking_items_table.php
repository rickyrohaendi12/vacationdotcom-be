<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->enum('itemable_type', ['flight', 'train', 'hotel']); // jenis item
            $table->unsignedBigInteger('itemable_id'); // ID ke seats.id atau room_types.id
            $table->decimal('price', 12, 2); // harga saat dibeli (snapshot)
            $table->json('details')->nullable(); // tanggal checkin/checkout untuk hotel, dll
            $table->timestamps();

            $table->index(['itemable_type', 'itemable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
