<?php

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\User;
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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->enum('status', RentalStatus::values())->index();
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->decimal('damage_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->enum('payment_status', PaymentStatus::values())->default(PaymentStatus::UNPAID->value);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
