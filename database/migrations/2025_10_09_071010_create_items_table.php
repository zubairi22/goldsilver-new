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
            $table->double('price_buy');
            $table->double('price_sell');
            $table->enum('status', ['ready', 'sold', 'damaged', 'not_ready'])->default('ready');
            $table->string('qr_path')->nullable();
            $table->string('source')->default('stock');
            $table->timestamps();
            $table->softDeletes();
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
