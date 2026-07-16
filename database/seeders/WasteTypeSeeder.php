<?php

namespace Database\Seeders;

use App\Models\WasteType;
use Illuminate\Database\Seeder;

class WasteTypeSeeder extends Seeder
{
    /**
     * Seed loại rác chuẩn hệ thống (idempotent theo name).
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Giấy bìa', 'icon' => '📄'],
            ['name' => 'Giấy văn phòng', 'icon' => '📃'],
            ['name' => 'Nhựa PET', 'icon' => '🧴'],
            ['name' => 'Nhựa cứng', 'icon' => '♻️'],
            ['name' => 'Lon nhôm', 'icon' => '🥫'],
            ['name' => 'Sắt / kim loại', 'icon' => '🔩'],
            ['name' => 'Rác hữu cơ', 'icon' => '🍃'],
            ['name' => 'Thủy tinh', 'icon' => '🫙'],
            ['name' => 'Pin / ắc quy', 'icon' => '🔋'],
            ['name' => 'Đồ điện tử', 'icon' => '💻'],
        ];

        foreach ($types as $type) {
            WasteType::query()->updateOrCreate(
                ['name' => $type['name'], 'is_system' => true],
                [
                    'icon' => $type['icon'],
                    'created_by' => null,
                ]
            );
        }
    }
}
