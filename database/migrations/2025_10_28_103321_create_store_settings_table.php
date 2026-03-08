<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();

            $table->string('category');
            $table->string('store_name');

            $table->string('phone')->nullable();
            $table->string('instagram')->nullable();
            $table->string('address')->nullable();

            $table->string('invoice_color')->default('#FFD700');

            $table->text('header')->nullable();
            $table->text('footer_wholesale')->nullable();
            $table->text('footer_retail')->nullable();

            $table->timestamps();

            $table->unique('category');
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
