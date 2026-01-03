<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buyback_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyback_id')->constrained('buybacks')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->unsignedBigInteger('old_barang_id')->nullable()->comment('ID barang manual dari sistem lama');
            $table->string('manual_name')->nullable();
            $table->decimal('weight', 10)->nullable();
            $table->double('price');
            $table->double('subtotal');
            $table->enum('condition', ['good','broken'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyback_items');
    }
};
