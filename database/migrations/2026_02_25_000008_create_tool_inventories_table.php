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
        Schema::create('tool_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_stock')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_inventories');
    }
};
