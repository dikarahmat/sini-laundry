<?php

namespace App\Services;

use App\Models\Service;

/**
 * Perhitungan harga pesanan (PRD.md bagian 7):
 * - berat dibulatkan ke atas per 0,5 kg, minimal sesuai config laundry.min_weight_kg
 *   untuk layanan kiloan
 * - subtotal = berat dibulatkan x harga layanan
 * - layanan is_express: tambah express_surcharge_percent dari config/laundry.php
 * - total = jumlah subtotal
 * - layanan pcs: kuantitas bulat, tanpa pembulatan 0,5
 * Uang selalu integer rupiah.
 */
class OrderPricingService
{
    /**
     * Hitung harga sekumpulan item.
     *
     * @param  array<int, array{service_id: int, quantity: float}>  $items
     * @return array{items: array<int, array{service_id: int, service_name: string, quantity: float, unit_price: int, subtotal: int}>, total: int, total_weight: float}
     */
    public function calculate(array $items): array
    {
        $calculatedItems = [];
        $total = 0;
        $totalWeight = 0.0;

        foreach ($items as $item) {
            $service = Service::findOrFail($item['service_id']);
            $quantity = $this->normalizeQuantity($service, (float) $item['quantity']);

            $subtotal = (int) round($service->price * $quantity);

            if ($service->is_express) {
                $subtotal += $this->expressSurcharge($subtotal);
            }

            $calculatedItems[] = [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'quantity' => $quantity,
                'unit_price' => $service->price,
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;

            if ($service->unit->value === 'kg') {
                $totalWeight += $quantity;
            }
        }

        return [
            'items' => $calculatedItems,
            'total' => $total,
            'total_weight' => round($totalWeight, 2),
        ];
    }

    /**
     * Berat/kg dibulatkan ke atas per 0,5 kg dengan batas minimum konfigurasi.
     * Satuan pcs dibulatkan ke bilangan bulat (minimal 1).
     */
    public function normalizeQuantity(Service $service, float $quantity): float
    {
        if ($service->unit->value === 'kg') {
            $rounded = ceil(round($quantity * 2, 6)) / 2;
            $min = (float) config('laundry.min_weight_kg', 1);

            return round(max($rounded, $min), 2);
        }

        return (float) max(1, (int) ceil($quantity));
    }

    /**
     * Tambahan biaya express dalam rupiah.
     */
    private function expressSurcharge(int $subtotal): int
    {
        $percent = (int) config('laundry.express_surcharge_percent', 50);

        return (int) round($subtotal * $percent / 100);
    }
}
