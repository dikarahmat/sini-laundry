<?php

namespace Tests\Unit;

use App\Enums\ServiceUnit;
use App\Models\Service;
use App\Services\OrderPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new OrderPricingService;
    }

    /**
     * Buat layanan kiloan dengan harga tertentu.
     */
    private function kgService(int $price, bool $express = false): Service
    {
        return Service::create([
            'name' => $express ? 'Express' : 'Cuci Kering',
            'unit' => ServiceUnit::Kilogram,
            'price' => $price,
            'duration_hours' => 48,
            'is_express' => $express,
            'is_active' => true,
        ]);
    }

    public function test_berat_dibulatkan_ke_atas_per_05_kg(): void
    {
        $this->assertSame(1.0, $this->service->normalizeQuantity($this->kgService(6000), 0.3));
        $this->assertSame(1.5, $this->service->normalizeQuantity($this->kgService(6000), 1.2));
        $this->assertSame(2.0, $this->service->normalizeQuantity($this->kgService(6000), 2.0));
        $this->assertSame(1.5, $this->service->normalizeQuantity($this->kgService(6000), 1.5));
        $this->assertSame(2.5, $this->service->normalizeQuantity($this->kgService(6000), 2.1));
    }

    public function test_layanan_pcs_tanpa_pembulatan_05(): void
    {
        $pcs = Service::create([
            'name' => 'Jaket',
            'unit' => ServiceUnit::Piece,
            'price' => 20000,
            'duration_hours' => 72,
            'is_express' => false,
            'is_active' => true,
        ]);

        $this->assertSame(3.0, $this->service->normalizeQuantity($pcs, 2.4));
        $this->assertSame(1.0, $this->service->normalizeQuantity($pcs, 1));
    }

    public function test_subtotal_kilaan(): void
    {
        $service = $this->kgService(6000);

        $result = $this->service->calculate([
            ['service_id' => $service->id, 'quantity' => 2.4],
        ]);

        $this->assertSame(15000, $result['total']);
        $this->assertSame(2.5, $result['total_weight']);
        $this->assertSame(2.5, $result['items'][0]['quantity']);
        $this->assertSame(6000, $result['items'][0]['unit_price']);
    }

    public function test_tambahan_biaya_express(): void
    {
        // config('laundry.express_surcharge_percent') default 50.
        $service = $this->kgService(8000, express: true);

        $result = $this->service->calculate([
            ['service_id' => $service->id, 'quantity' => 1.0],
        ]);

        // 8000 + 50% = 12000
        $this->assertSame(12000, $result['total']);
        $this->assertSame(12000, $result['items'][0]['subtotal']);
    }

    public function test_total_untuk_beberapa_layanan(): void
    {
        $kg = $this->kgService(6000);
        $pcs = Service::create([
            'name' => 'Selimut',
            'unit' => ServiceUnit::Piece,
            'price' => 25000,
            'duration_hours' => 72,
            'is_express' => false,
            'is_active' => true,
        ]);

        $result = $this->service->calculate([
            ['service_id' => $kg->id, 'quantity' => 2.0],   // 2.0 x 6000 = 12000
            ['service_id' => $pcs->id, 'quantity' => 1],   // 1 x 25000 = 25000
        ]);

        $this->assertSame(37000, $result['total']);
        $this->assertSame(2.0, $result['total_weight']); // hanya layanan kg yang dihitung beratnya
    }
}
