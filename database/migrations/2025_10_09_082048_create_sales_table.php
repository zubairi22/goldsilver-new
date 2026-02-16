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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->enum('category', ['gold', 'silver'])->default('gold');
            $table->enum('sale_type', ['retail', 'wholesale'])->default('retail');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_weight', 10, 2)->default(0);
            $table->integer('total_price')->default(0);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->integer('paid_amount')->default(0);
            $table->integer('remaining_amount')->default(0);
            $table->integer('change_amount')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('paid');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('qr_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
