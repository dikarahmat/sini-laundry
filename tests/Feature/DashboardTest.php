<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_dialihkan_ke_halaman_sesuai_peran(): void
    {
        $customer = User::factory()->role(Role::Customer)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $owner = User::factory()->role(Role::Owner)->create();

        $this->actingAs($customer)->get(route('dashboard'))->assertRedirect(route('pesanan.index'));
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.pesanan.index'));
        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('owner.dashboard'));
    }
}
