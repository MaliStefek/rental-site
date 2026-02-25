<?php

use App\Models\Category;
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
        Schema::create('tools', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnDelete();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->unsignedInteger('total_stock')->default(0);
            $table->unsignedInteger('available_stock')->default(0);
            $table->decimal('price_hourly', 10, 2)->nullable();
            $table->decimal('price_daily', 10, 2)->nullable();
            $table->decimal('price_weekly', 10, 2)->nullable();
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
