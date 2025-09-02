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
        Schema::create('transaction_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique()->index();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('total_amount')->default(0)->index();
            $table->foreignId('financial_account_id')->nullable()->constrained('financial_accounts')->nullOnDelete();
            $table->string('external_reference')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('refunded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('refunded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_refunds');
    }
};
