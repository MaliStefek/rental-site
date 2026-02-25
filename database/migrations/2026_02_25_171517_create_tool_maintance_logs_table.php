<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tool;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tool_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tool::class)->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2);
            $table->date('maintenance_date');
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
