<?php

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', RentalStatus::values())->index();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->dateTime('returned_at')->nullable();

            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('late_fee_cents')->default(0);
            $table->unsignedBigInteger('damage_fee_cents')->default(0);
            $table->unsignedBigInteger('total_cents');
            $table->unsignedBigInteger('paid_cents')->default(0);

            $table->enum('payment_status', PaymentStatus::values())->default(PaymentStatus::UNPAID->value)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
