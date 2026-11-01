<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wholesale pricing (ADR-0019): optional wholesale price and minimum quantity per product,
 * a wholesale flag on customers, and the price tier recorded on sales and sale lines.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('wholesale_price_pesewas')->nullable()->after('price_pesewas');
            $table->unsignedInteger('wholesale_min_quantity')->default(1)->after('wholesale_price_pesewas');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_wholesale')->default(false)->after('notes');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('price_tier', 10)->default('retail')->after('sold_by');
        });

        Schema::table('sale_lines', function (Blueprint $table) {
            $table->string('price_tier', 10)->default('retail')->after('unit_price_pesewas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_lines', fn (Blueprint $table) => $table->dropColumn('price_tier'));
        Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('price_tier'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('is_wholesale'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['wholesale_price_pesewas', 'wholesale_min_quantity']));
    }
};
