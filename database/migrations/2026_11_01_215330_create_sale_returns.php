<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Returns and cancellations (ADR-0020). A return is a new record against a sale; the sale itself is never
 * edited except for the running totals `returned_pesewas`, `paid_pesewas` and `status`.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('returned_pesewas')->default(0)->after('total_pesewas');
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->unsignedInteger('returned_quantity')->default(0)->after('quantity');
        });

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('return_number', 20)->unique();
            $table->foreignUuid('sale_id')->constrained('sales')->restrictOnDelete();
            $table->foreignUuid('processed_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->string('reason', 500);
            $table->unsignedBigInteger('credit_pesewas');
            $table->unsignedBigInteger('debt_reduction_pesewas');
            $table->unsignedBigInteger('refund_pesewas');
            $table->string('refund_method', 20)->nullable();
            $table->string('refund_reference', 100)->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamp('returned_at')->index();
            $table->timestamps();

            $table->index(['sale_id', 'returned_at']);
        });

        Schema::create('sale_return_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_return_id')->constrained('sale_returns')->restrictOnDelete();
            $table->foreignUuid('sale_line_id')->constrained('sale_lines')->restrictOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('value_pesewas');
            $table->boolean('restocked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_lines');
        Schema::dropIfExists('sale_returns');
        Schema::table('sale_lines', fn (Blueprint $table) => $table->dropColumn('returned_quantity'));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('returned_pesewas'));
    }
};
