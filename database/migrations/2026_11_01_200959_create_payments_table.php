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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->restrictOnDelete();
            $table->foreignUuid('received_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('amount_pesewas');
            $table->string('method', 20);
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamp('received_at')->index();
            $table->timestamps();

            $table->index(['customer_id', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
