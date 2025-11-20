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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->foreignId('item_type_id')->constrained('item_types')->cascadeOnDelete();
            $table->string('category')->default('gold');
            $table->double('weight');
            $table->decimal('price_buy', 15);
            $table->decimal('price_sell', 15);
            $table->enum('status', ['ready', 'sold', 'damaged', 'buyback', 'not_ready'])->default('ready');
            $table->string('qr_code')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
