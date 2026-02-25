<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Rental;
use \App\Enums\PaymentMethod;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Rental::class)->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', PaymentMethod::values());
            $table->string('transaction_reference')->nullable();
            $table->dateTime('paid_at');
            $table->timestamps();
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
