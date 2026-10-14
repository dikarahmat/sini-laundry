<?php

use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom peran dan nomor HP ke tabel users.
     * Migrasi baru: tidak mengubah atau menghapus kolom lama.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->after('email');
            $table->enum('role', array_column(Role::cases(), 'value'))
                ->default(Role::Customer->value)
                ->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'phone']);
        });
    }
};
