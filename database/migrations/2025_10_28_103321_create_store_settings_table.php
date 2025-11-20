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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            $table->string('store_name')->default('Toko Emas Kita');
            $table->string('phone')->nullable();
            $table->string('instagram')->nullable();

            $table->string('gold_invoice_color')->default('#FFD700');
            $table->string('silver_invoice_color')->default('#C0C0C0');

            $table->text('footer_gold_wholesale')->nullable();
            $table->text('footer_gold_retail')->nullable();
            $table->text('footer_silver_wholesale')->nullable();
            $table->text('footer_silver_retail')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
