<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_menampilkan_konten_sini_laundry(): void
    {
        $this->seed(\Database\Seeders\ServiceSeeder::class);

        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Cucianmu sudah sampai mana? Cek langsung di Sini.')
            ->assertSee('Cara kerja')
            ->assertSee('Layanan dan harga')
            ->assertSee('Pesan Laundry')
            ->assertSee('Cek Status Cucian')
            ->assertSee('Cuci + Setrika');
    }
}
