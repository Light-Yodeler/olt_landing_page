<?php

namespace Database\Seeders;

use App\Models\Standard;
use Illuminate\Database\Seeder;

class StandardSeeder extends Seeder
{
    public function run(): void
    {
        $standards = [
            [
                'title' => [
                    'en' => 'Joint Vehicle Inspection',
                    'id' => 'Pengecekan Fisik Bersama',
                ],
                'description' => [
                    'en' => 'Brakes, tires, lights, and fluids are thoroughly tested prior to handover. Mutual walkaround photos of body conditions prevent disputes upon return.',
                    'id' => 'Setiap kendaraan diperiksa fungsi rem, ban, lampu, dan oli sebelum diserahterimakan kepada Anda. Foto bersama kondisi bodi dilakukan di awal sewa untuk memastikan transparansi tanpa sengketa saat pengembalian.',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'title' => [
                    'en' => 'Responsive On-Road Assistance',
                    'id' => 'Bantuan Responsif',
                ],
                'description' => [
                    'en' => 'Encountering technical trouble on routes between Kuta, Mandalika, Selong Belanak, or Lombok Airport? Our operations team is on call to provide prompt solutions.',
                    'id' => 'Jika terjadi kendala teknis atau kebutuhan selama di rute Kuta, Mandalika, Selong Belanak, hingga Bandara, tim operasional kami siap mendampingi dan memberikan solusi cepat.',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => [
                    'en' => 'Licensed Guides & Safety',
                    'id' => 'Pemandu Jalur Berlisensi',
                ],
                'description' => [
                    'en' => 'Mount Rinjani expeditions are led by certified mountain guides trained in wilderness first aid. Surf coaches prioritize ocean safety for beginner students.',
                    'id' => 'Ekspedisi pendakian Gunung Rinjani dipandu oleh kru resmi yang terlatih standar pertolongan pertama gunung. Pelatih surfing memprioritaskan keselamatan ombak dan keamanan tamu pemula.',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($standards as $standard) {
            Standard::create($standard);
        }
    }
}
