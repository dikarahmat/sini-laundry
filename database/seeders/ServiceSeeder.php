<?php

namespace Database\Seeders;

use App\Enums\ServiceUnit;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Data layanan awal sesuai PRD.md bagian 5.1 dan prompt P5.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cuci Kering Lipat',
                'unit' => ServiceUnit::Kilogram,
                'price' => 6000,
                'duration_hours' => 48,
                'is_express' => false,
            ],
            [
                'name' => 'Cuci + Setrika',
                'unit' => ServiceUnit::Kilogram,
                'price' => 8000,
                'duration_hours' => 48,
                'is_express' => false,
            ],
            [
                'name' => 'Setrika Saja',
                'unit' => ServiceUnit::Kilogram,
                'price' => 5000,
                'duration_hours' => 24,
                'is_express' => false,
            ],
            [
                'name' => 'Express Cuci + Setrika',
                'unit' => ServiceUnit::Kilogram,
                'price' => 8000,
                'duration_hours' => 6,
                'is_express' => true,
            ],
            [
                'name' => 'Selimut',
                'unit' => ServiceUnit::Piece,
                'price' => 25000,
                'duration_hours' => 72,
                'is_express' => false,
            ],
            [
                'name' => 'Jaket',
                'unit' => ServiceUnit::Piece,
                'price' => 20000,
                'duration_hours' => 72,
                'is_express' => false,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                $service + ['is_active' => true],
            );
        }
    }
}
