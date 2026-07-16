<?php

namespace Database\Seeders;

use App\Models\MeasurementUnit;
use Illuminate\Database\Seeder;

class MeasurementUnitSeeder extends Seeder
{
    /**
     * Seed đơn vị đo hệ thống (idempotent theo symbol).
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Kilogram', 'symbol' => 'kg', 'category' => MeasurementUnit::CATEGORY_WEIGHT],
            ['name' => 'Gram', 'symbol' => 'g', 'category' => MeasurementUnit::CATEGORY_WEIGHT],
            ['name' => 'Milligram', 'symbol' => 'mg', 'category' => MeasurementUnit::CATEGORY_WEIGHT],
            ['name' => 'Tấn', 'symbol' => 't', 'category' => MeasurementUnit::CATEGORY_WEIGHT],
            ['name' => 'Lít', 'symbol' => 'l', 'category' => MeasurementUnit::CATEGORY_VOLUME],
            ['name' => 'Millilít', 'symbol' => 'ml', 'category' => MeasurementUnit::CATEGORY_VOLUME],
            ['name' => 'Cái', 'symbol' => 'pcs', 'category' => MeasurementUnit::CATEGORY_COUNT],
            ['name' => 'Bịch', 'symbol' => 'bag', 'category' => MeasurementUnit::CATEGORY_COUNT],
        ];

        foreach ($units as $unit) {
            MeasurementUnit::query()->updateOrCreate(
                ['symbol' => $unit['symbol']],
                [
                    'name' => $unit['name'],
                    'category' => $unit['category'],
                    'is_system' => true,
                    'created_by' => null,
                ]
            );
        }
    }
}
