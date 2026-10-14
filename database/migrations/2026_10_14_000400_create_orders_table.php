<?php

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pesanan utama.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('source', array_column(OrderSource::cases(), 'value'));
            $table->enum('status', array_column(OrderStatus::cases(), 'value'))
                ->default(OrderStatus::PendingConfirmation->value);
            $table->enum('payment_status', array_column(PaymentStatus::cases(), 'value'))
                ->default(PaymentStatus::Unpaid->value);
            $table->decimal('total_weight', 6, 2)->default(0); // kg
            $table->unsignedInteger('total_price')->default(0); // rupiah
            $table->text('notes')->nullable();
            $table->timestamp('estimated_done_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
