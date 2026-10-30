<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'category_code' => ['en' => 'SCOOTER RENTAL', 'id' => 'SEWA MOTOR'],
                'icon' => 'bike',
                'title' => ['en' => 'Motorbike & Scooter Rental', 'id' => 'Sewa Sepeda Motor'],
                'description' => [
                    'en' => "Comfortable automatic scooters ready to explore Kuta's scenic beaches and Mandalika coastal roads with total freedom.",
                    'id' => 'Kendaraan roda dua yang nyaman dan siap pakai untuk menjelajahi pantai-pantai di Kuta dan kawasan Mandalika secara leluasa.',
                ],
                'features' => [
                    'en' => ['Includes 2 clean SNI/DOT helmets', 'Delivery to your hotel around Kuta area'],
                    'id' => ['Dilengkapi 2 helm SNI bersih', 'Layanan antar ke penginapan area Kuta'],
                ],
                'button_text' => ['en' => 'Check Scooters', 'id' => 'Tanya Unit Motor'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like to inquire about scooter rentals in Kuta Lombok.',
                    'id' => 'Halo OLT, saya ingin menanyakan sewa sepeda motor di Kuta Lombok.',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'category_code' => ['en' => 'TRANSPORTATION', 'id' => 'TRANSPORTASI'],
                'icon' => 'car',
                'title' => ['en' => 'Car Charter & Airport Transfer', 'id' => 'Mobil & Antar-Jemput'],
                'description' => [
                    'en' => 'Punctual pick-up service for Lombok International Airport (LOP), seaports, and daily private car charters with friendly drivers across the island.',
                    'id' => 'Layanan penjemputan Bandara Lombok (LOP), pelabuhan, maupun carter mobil harian bersama sopir berpengalaman untuk rute seluruh pulau.',
                ],
                'features' => [
                    'en' => ['Punctual service matching flight arrivals', 'Clean, comfortable, and air-conditioned cars'],
                    'id' => ['Tepat waktu sesuai tiket kedatangan', 'Armada bersih, nyaman, dan ber-AC'],
                ],
                'button_text' => ['en' => 'Check Transport', 'id' => 'Tanya Transport'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I need car charter / Lombok airport transfer service.',
                    'id' => 'Halo OLT, saya butuh layanan mobil / antar-jemput bandara Lombok.',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'category_code' => ['en' => 'TREKKING & JUNGLE', 'id' => 'GUNUNG & CURUG'],
                'icon' => 'mountain',
                'title' => ['en' => 'Rinjani Trekking & Waterfalls', 'id' => 'Trekking Rinjani & Air Terjun'],
                'description' => [
                    'en' => 'Curated trekking packages to Mount Rinjani and day tours to Tiu Kelep & Benang Kelambu waterfalls, guided by licensed local mountain crews.',
                    'id' => 'Program pendakian ke Gunung Rinjani serta wisata air terjun Tiu Kelep dan Benang Kelambu, berkolaborasi dengan kru pemandu dan porter berlisensi.',
                ],
                'features' => [
                    'en' => ['Organized camping & climbing logistics', 'Certified guides familiar with routes & safety'],
                    'id' => ['Paket pendakian terorganisasi rapi', 'Pemandu yang memahami jalur dan medan'],
                ],
                'button_text' => ['en' => 'Inquire Rinjani', 'id' => 'Tanya Info Rinjani'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I am interested in trekking Mount Rinjani / Waterfall day trips.',
                    'id' => 'Halo OLT, saya ingin menanyakan paket tur Gunung Rinjani / Air Terjun.',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'category_code' => ['en' => 'BEACH & ISLANDS', 'id' => 'PANTAI & PULAU'],
                'icon' => 'waves',
                'title' => ['en' => 'Surfing Lessons & 3 Gili Trips', 'id' => 'Surfing & Trip 3 Gili'],
                'description' => [
                    'en' => 'Beginner surf coaching, boat transfers to Gerupuk surf breaks, and private boat snorkeling charters to Gili Trawangan, Meno, and Air.',
                    'id' => 'Kelas belajar selancar bagi pemula, carter boat ke titik ombak Gerupuk, serta perjalanan snorkeling private boat di kepulauan Gili Trawangan, Meno, dan Air.',
                ],
                'features' => [
                    'en' => ['Friendly and patient surf instructors', 'Boat charter & snorkeling gear included'],
                    'id' => ['Instruktur surfing yang ramah & sabar', 'Penyeberangan perahu & alat snorkeling'],
                ],
                'button_text' => ['en' => 'Inquire Surf & Gili', 'id' => 'Tanya Surf & Gili'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like information regarding surf lessons / Gili snorkeling trips.',
                    'id' => 'Halo OLT, saya ingin info mengenai kelas surfing / trip snorkeling Gili.',
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
