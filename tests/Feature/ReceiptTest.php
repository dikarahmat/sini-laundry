<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->order = Order::factory()->create([
            'code' => 'SL-20261014-001',
            'status' => OrderStatus::Received,
            'payment_status' => PaymentStatus::Unpaid,
            'total_price' => 15000,
        ]);
    }

    public function test_nota_menampilkan_kode_dan_total(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.pesanan.nota', $this->order))
            ->assertOk()
            ->assertSee('SL-20261014-001')
            ->assertSee('Rp 15.000')
            ->assertSee('Terima kasih telah menggunakan');
    }

    public function test_nota_hanya_untuk_admin_dan_owner(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();

        $this->actingAs($customer)->get(route('admin.pesanan.nota', $this->order))->assertForbidden();

        $owner = User::factory()->role(Role::Owner)->create();
        $this->actingAs($owner)->get(route('admin.pesanan.nota', $this->order))->assertOk();
    }

    public function test_nota_memuat_tautan_cek_status(): void
    {
        $admin = User::factory()->role(Role::Admin)->create();

        $this->actingAs($admin)
            ->get(route('admin.pesanan.nota', $this->order))
            ->assertSee(url('/cek-status?kode=SL-20261014-001', [], false));
    }
}
