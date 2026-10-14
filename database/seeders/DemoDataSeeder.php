<?php

namespace Database\Seeders;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Services\OrderPricingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data demo untuk screenshot dan presentasi laporan SIM.
 * Jalankan dengan: php artisan db:seed --class=DemoDataSeeder
 * Semua data fiktif. Aman dijalankan ulang (data demo lama dibersihkan dulu).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->error('DemoDataSeeder tidak boleh dijalankan di produksi.');

            return;
        }

        $this->callOnce(ServiceSeeder::class);

        // Hanya satu kasir/admin demo untuk created_by.
        $staff = User::firstWhere('email', 'admin@sinilaundry.test') ?? User::factory()->create([
            'name' => 'Pak Budi (Admin)',
            'email' => 'admin@sinilaundry.test',
            'phone' => '081200000002',
            'role' => 'admin',
        ]);

        $this->command->warn('DemoDataSeeder akan MENGHAPUS semua pesanan dan pelanggan walk-in yang ada, lalu membuat data demo baru.');

        DB::transaction(function () use ($staff): void {
            // Bersihkan data demo lama agar aman dijalankan ulang.
            OrderStatusLog::query()->delete();
            Payment::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();
            Customer::query()->whereNull('user_id')->delete();

            $services = Service::where('is_active', true)->get();

            $names = [
                'Rina Melati', 'Agus Setiawan', 'Siti Nurhaliza', 'Budi Santoso', 'Dewi Lestari',
                'Eko Prasetyo', 'Fitri Handayani', 'Hendra Wijaya', 'Indah Permata', 'Joko Susilo',
            ];

            // 10 pelanggan (walk-in fiktif).
            $customers = [];

            foreach ($names as $index => $name) {
                $customers[] = Customer::create([
                    'name' => $name,
                    'phone' => sprintf('0812%08d', 30000000 + $index * 137),
                    'address' => 'Jl. Contoh No. '.($index + 1).', Kota Fiktif',
                ]);
            }

            $pricing = app(OrderPricingService::class);

            /** @var array<string, int> $usedNumbers */
            $usedNumbers = [];

            /** @var array<int, class-string<OrderStatus>> $statuses */
            $statuses = [
                OrderStatus::PickedUp, OrderStatus::PickedUp,
                OrderStatus::Done, OrderStatus::Done,
                OrderStatus::Ironing, OrderStatus::Washing,
                OrderStatus::Received, OrderStatus::Received,
                OrderStatus::PendingConfirmation,
                OrderStatus::Cancelled,
            ];

            foreach (range(1, 30) as $i) {
                $createdAt = Carbon::now()->subDays(random_int(0, 29))->setTime(random_int(8, 19), random_int(0, 59));

                $customer = $customers[array_rand($customers)];

                $isOnline = random_int(0, 3) === 0;

                $service = $services->random();
                $quantity = $service->unit->value === 'kg'
                    ? round(random_int(10, 45) / 10, 1)
                    : random_int(1, 3);

                $result = $pricing->calculate([[
                    'service_id' => $service->id,
                    'quantity' => $quantity,
                ]]);

                $status = $isOnline && $i % 7 === 0
                    ? OrderStatus::PendingConfirmation
                    : $statuses[array_rand($statuses)];

                $order = Order::create([
                    'code' => $this->demoCodeFor($createdAt, $usedNumbers),
                    'customer_id' => $customer->id,
                    'created_by' => $isOnline ? null : $staff->id,
                    'source' => $isOnline ? OrderSource::Online : OrderSource::WalkIn,
                    'status' => $status,
                    'payment_status' => PaymentStatus::Unpaid,
                    'total_weight' => $result['total_weight'],
                    'total_price' => $result['total'],
                    'notes' => random_int(0, 4) === 0 ? 'Pisahkan pakaian putih' : null,
                    'estimated_done_at' => (clone $createdAt)->addHours($service->duration_hours),
                    'picked_up_at' => $status === OrderStatus::PickedUp
                        ? (clone $createdAt)->addHours($service->duration_hours + random_int(1, 10))
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $order->items()->create([
                    'service_id' => $service->id,
                    'quantity' => $result['items'][0]['quantity'],
                    'unit_price' => $result['items'][0]['unit_price'],
                    'subtotal' => $result['items'][0]['subtotal'],
                ]);

                $this->createStatusLogs($order, $status, $createdAt, $isOnline ? null : $staff->id);

                // Pembayaran bervariasi: lunas, DP, atau belum bayar.
                if ($status === OrderStatus::Cancelled) {
                    // Pesanan batal: tanpa pembayaran.
                } elseif ($status === OrderStatus::PickedUp || random_int(0, 2) > 0) {
                    $fullPaid = $status === OrderStatus::PickedUp || random_int(0, 1) === 0;

                    if ($fullPaid) {
                        $order->payments()->create([
                            'amount' => $order->total_price,
                            'method' => PaymentMethod::cases()[array_rand(PaymentMethod::cases())],
                            'received_by' => $staff->id,
                            'paid_at' => (clone $createdAt)->addHour(),
                        ]);

                        $order->update(['payment_status' => PaymentStatus::Paid]);
                    } else {
                        $dp = (int) round($order->total_price * 0.5 / 500) * 500;

                        $order->payments()->create([
                            'amount' => $dp,
                            'method' => PaymentMethod::Cash,
                            'received_by' => $staff->id,
                            'paid_at' => (clone $createdAt)->addHour(),
                        ]);

                        $order->update(['payment_status' => PaymentStatus::Partial]);
                    }
                }
            }
        });
    }

    /**
     * Kode pesanan demo unik per tanggal (data lama sudah dibersihkan lebih dulu).
     *
     * @param  array<string, int>  $usedNumbers
     */
    private function demoCodeFor(Carbon $createdAt, array &$usedNumbers): string
    {
        $prefix = 'SL-'.$createdAt->format('Ymd').'-';
        $usedNumbers[$prefix] = ($usedNumbers[$prefix] ?? 0) + 1;

        return $prefix.str_pad((string) $usedNumbers[$prefix], 3, '0', STR_PAD_LEFT);
    }

    /**
     * Buat riwayat status yang konsisten dengan status akhir pesanan.
     *
     * @param  array<int, string>  ... (tidak diperlukan)
     */
    private function createStatusLogs(Order $order, OrderStatus $finalStatus, Carbon $createdAt, ?int $changedBy): void
    {
        $flow = [OrderStatus::PendingConfirmation, OrderStatus::Received, OrderStatus::Washing, OrderStatus::Ironing, OrderStatus::Done, OrderStatus::PickedUp];

        if ($finalStatus === OrderStatus::Cancelled) {
            $flow = [OrderStatus::PendingConfirmation, OrderStatus::Received, OrderStatus::Cancelled];
        } else {
            $position = array_search($finalStatus, $flow, true);
            $flow = array_slice($flow, 0, $position + 1);
        }

        $logTime = $createdAt->copy();

        foreach ($flow as $index => $status) {
            $logTime = $logTime->copy()->addHours(random_int(1, 20));

            OrderStatusLog::create([
                'order_id' => $order->id,
                'status' => $status,
                'changed_by' => $status === OrderStatus::PendingConfirmation ? null : $changedBy,
                'note' => $status === OrderStatus::Cancelled ? 'Demo: pelanggan batal' : null,
                'created_at' => $logTime,
            ]);
        }
    }
}
