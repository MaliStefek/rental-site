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
        Schema::create('tool_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->text('description')->nullable();
            $table->date('maintenance_date')->index();
            $table->date('next_due_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_maintenance_logs');
    }
};
