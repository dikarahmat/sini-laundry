<?php

use App\Enums\ServiceUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel layanan laundry beserta harga.
     */
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->enum('unit', array_column(ServiceUnit::cases(), 'value'));
            $table->unsignedInteger('price'); // rupiah, tanpa desimal
            $table->unsignedSmallInteger('duration_hours'); // estimasi selesai
            $table->boolean('is_express')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
