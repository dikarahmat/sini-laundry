<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('pesanan.index', absolute: false));

        $this->assertAuthenticated();

        // Semua pendaftar lewat halaman publik menjadi pelanggan.
        $user = User::whereEmail('test@example.com')->firstOrFail();
        $this->assertTrue($user->isCustomer());
        $this->assertSame('081234567890', $user->phone);
    }

    public function test_register_requires_a_valid_phone_number(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }

    public function test_register_cannot_set_role_from_the_form(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'phone' => '081234567890',
            'role' => 'owner',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasNoErrors();

        $user = User::whereEmail('test@example.com')->firstOrFail();
        $this->assertTrue($user->isCustomer());
    }
}
