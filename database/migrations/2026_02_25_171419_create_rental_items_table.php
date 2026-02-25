<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Rental;
use App\Models\Tool;
use \App\Enums\PricingType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rental_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Rental::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Tool::class)->constrained()->cascadeOnDelete();
            $table->unique(['rental_id', 'tool_id']);
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->enum('pricing_type', PricingType::values())->index();
            $table->timestamps();
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
