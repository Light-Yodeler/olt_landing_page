<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    public function run(): void
    {
        $slides = [
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDrrFc2H2QCG30Byd1mOKlytkwoqzF4407G5laJXcUDeyZkkW6TMRgCJlsQ5SI4ddzjjniMerw2GFhzEYqYPOPuN7awKOavR9DEt6pvz9q6Pbpozjv8PiLqK2DZHGAP3pocNmtT4GXkOL-1XUxDQW0tPxRQXE9kHxF0b_uD14UW-wmeC0zB5aqS7_a_Y4o6ulONEcI7_eeFTbjJpGejgTPTgEDJ3UBwFZQqqvMAiGW2',
                'caption' => [
                    'en' => 'Merese Hill & Kuta Mandalika Coastline',
                    'id' => 'Bukit Merese & Pantai Kuta Mandalika',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCbyKL6C2yWOdv7YDBK_dsovf_8TpaVQ8AJe7KUb43wYuAqO-le5o0jMqqXq_9W0HSb_xibuNy63uYdRM3sRYNOIwqZ2E6pwfcyXeJ8g-nqtUXkjlXJ_mpBRVThmb_tmzPHV8DzdU2r_As4iCANCGtl-mnfaI-yc3IAUwOI13JEgQVJDbfuA6RaB6vj1lXkbiuZdHuMg8FVa57qt2asmdH-xCpq6zHC-OFMMTsCPTta',
                'caption' => [
                    'en' => 'Mount Rinjani & Segara Anak Crater Lake',
                    'id' => 'Panorama Gunung Rinjani & Segara Anak',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB98bL0n8-xFNnTcJGOCRqvt84rzwkzQWU2abAUOlQ73C7_lN5w5lymzdwSKSbAose_WM4_OBB_LSafcIq5cQFyXMS6-7uSlJl-ZjpXURS5UT5VkVpCpsr0u_s8LigrFOdZyizVGk1q2PAsi-7ZMrnx0Kwnu4KAIx4VaQ5No75RYQO7KpR8osxchjZoUvKv3J7eAVl99uvmxYPrLd5-6PsJMDbU_hQVwskjwfMsWHXM',
                'caption' => [
                    'en' => 'Crystal Clear Waters of Selong Belanak & Gili Islands',
                    'id' => 'Keindahan Pantai & Air Jernih Selong Belanak / Gili',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAqRxF4YfP3XdTmduXfvp3788JXXSIQeru22js1spWQhbzLsLkMa2NuxBKll_WLMwr1kCsdjChNuIO8yTptXkSPcmOLu9tIJb4wRWX_5Rof98VayaU8xTpGrWrDiPzOc64xwD0eaZghpOiLOwsp7Uc9kq5rBdebYIwwg4FPJl4YLHL6qARSKlF9-W-kYcenA2QvaRxltSieTDHm2edCllO34SVJ8D3er6zGwRh71jeh',
                'caption' => [
                    'en' => 'Tiu Kelep Waterfall in Senaru, North Lombok',
                    'id' => 'Air Terjun Tiu Kelep Rinjani',
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($slides as $slide) {
            HeroSlide::create($slide);
        }
    }
}
