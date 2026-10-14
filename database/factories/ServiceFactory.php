<?php

namespace Database\Factories;

use App\Enums\ServiceUnit;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Service::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Cuci Kering Lipat', 'Cuci + Setrika', 'Setrika Saja', 'Selimut', 'Jaket']),
            'unit' => fake()->randomElement(ServiceUnit::cases()),
            'price' => fake()->numberBetween(5000, 25000) * 100,
            'duration_hours' => fake()->randomElement([6, 24, 48, 72]),
            'is_express' => false,
            'is_active' => true,
        ];
    }

    /**
     * Layanan kiloan.
     */
    public function perKg(): static
    {
        return $this->state(fn (array $attributes): array => [
            'unit' => ServiceUnit::Kilogram,
        ]);
    }

    /**
     * Layanan satuan.
     */
    public function perPiece(): static
    {
        return $this->state(fn (array $attributes): array => [
            'unit' => ServiceUnit::Piece,
        ]);
    }

    /**
     * Layanan express.
     */
    public function express(): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Express Cuci + Setrika',
            'unit' => ServiceUnit::Kilogram,
            'is_express' => true,
            'duration_hours' => 6,
        ]);
    }
}
