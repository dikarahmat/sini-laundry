<?php

namespace App\Models;

use App\Enums\ServiceUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property ServiceUnit $unit
 * @property int $price
 * @property int $duration_hours
 * @property bool $is_express
 * @property bool $is_active
 * @property \Carbon\CarbonInterface $created_at
 * @property \Carbon\CarbonInterface $updated_at
 */
class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'unit',
        'price',
        'duration_hours',
        'is_express',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit' => ServiceUnit::class,
            'price' => 'integer',
            'duration_hours' => 'integer',
            'is_express' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
