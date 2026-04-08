<?php

use App\Enums\PricingType;
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
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->enum('pricing_type', PricingType::values());
            $table->unsignedBigInteger('unit_price_cents');
            $table->timestamps();

            $table->unique(['rental_id', 'tool_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_items');
    }
};
