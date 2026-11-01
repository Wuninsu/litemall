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
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('receipt_number', 20)->unique();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignUuid('sold_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('subtotal_pesewas');
            $table->unsignedBigInteger('discount_pesewas')->default(0);
            $table->string('discount_reason')->nullable();
            $table->unsignedBigInteger('total_pesewas');
            $table->unsignedBigInteger('paid_pesewas')->default(0);
            $table->string('status', 20)->index();
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamp('sold_at')->index();
            $table->timestamps();

            $table->index(['customer_id', 'status', 'sold_at']);
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
