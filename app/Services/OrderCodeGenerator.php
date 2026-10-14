<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Generator kode pesanan: SL-YYYYMMDD-NNN (urutan 3 digit per hari).
 * Dibuat di dalam transaksi dengan lock agar aman dari kode kembar.
 */
class OrderCodeGenerator
{
    /**
     * Buat kode pesanan baru untuk hari ini (zona waktu aplikasi).
     */
    public function generate(): string
    {
        return DB::transaction(function (): string {
            $today = Carbon::now(config('app.timezone'))->toDateString();
            $prefix = 'SL-'.Carbon::now(config('app.timezone'))->format('Ymd').'-';

            $lastCode = Order::query()
                ->whereDate('created_at', $today)
                ->where('code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $next = $lastCode === null
                ? 1
                : ((int) substr($lastCode, strlen($prefix)) + 1);

            return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        });
    }
}
