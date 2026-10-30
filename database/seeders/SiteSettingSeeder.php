<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::updateOrCreate(
            ['id' => 1],
            [
                'company_name' => [
                    'en' => 'Ocean Lombok Tour & Transport',
                    'id' => 'Ocean Lombok Tour & Transport',
                ],
                'tagline' => [
                    'en' => 'Seamless motorbike rental, car charter, airport transfers, and private tours across Lombok from Kuta Mandalika.',
                    'id' => 'Rental motor, carter mobil, antar-jemput Bandara Lombok (LOP), dan tur privat Rinjani, Gili, serta surfing dari Kuta Mandalika.',
                ],
                'whatsapp_number' => '6287865432190',
                'whatsapp_default_message' => [
                    'en' => 'Hello Ocean Lombok Tour, I would like to inquire about transport and tour services in Lombok.',
                    'id' => 'Halo Ocean Lombok Tour, saya ingin berkonsultasi seputar layanan sewa dan tur di Lombok.',
                ],
                'address' => 'Kuta Mandalika, Lombok Tengah, Nusa Tenggara Barat, Indonesia',
                'instagram_url' => 'https://instagram.com',
                'facebook_url' => 'https://facebook.com',
                'tiktok_url' => 'https://tiktok.com',
                'tripadvisor_url' => 'https://tripadvisor.com',
                'google_maps_url' => 'https://maps.google.com',
                'footer_copyright' => [
                    'en' => '© 2026 Ocean Lombok Tour and Transport (OLT). All rights reserved.',
                    'id' => '© 2026 Ocean Lombok Tour and Transport (OLT). Hak cipta dilindungi.',
                ],
                'hero_badge' => [
                    'en' => 'Tour & Transport Service in Kuta Mandalika',
                    'id' => 'Layanan Tour & Transportasi Kuta Mandalika',
                ],
                'hero_headline' => [
                    'en' => 'Explore Lombok effortlessly. Rent vehicles and plan your custom itinerary with our local team.',
                    'id' => 'Eksplorasi Lombok dengan mudah. Sewa kendaraan dan atur rute bersama tim kami.',
                ],
                'hero_description' => [
                    'en' => 'We provide scooter rentals, private car charters, airport transfers, and bespoke island tours across Lombok. Supported by well-maintained fleets and a trusted local network, we ensure your journey is safe, comfortable, and memorable.',
                    'id' => 'Kami melayani rental motor, carter mobil, antar-jemput bandara, hingga trip wisata di seluruh Lombok. Didukung armada operasional yang terawat serta rekanan terpercaya di berbagai titik, kami siap membantu perjalanan Anda agar nyaman, aman, dan lancar.',
                ],
                'hero_highlights' => [
                    'en' => [
                        'Well-maintained motorbikes for daily & weekly rentals',
                        'Reliable car charter & Lombok Airport (LOP) transfers',
                        'Integrated network with verified local transport partners',
                        'Licensed guides for Rinjani trekking, waterfalls, surfing & Gili trips',
                    ],
                    'id' => [
                        'Unit motor prima & terawat untuk harian / mingguan',
                        'Layanan antar-jemput mobil di hotel atau Bandara (LOP)',
                        'Layanan terintegrasi dengan jaringan transportasi yang aman',
                        'Pemandu trekking Rinjani, air terjun, surf, & Gili trip',
                    ],
                ],
                'about_badge' => [
                    'en' => '[ ABOUT OUR SERVICES ]',
                    'id' => '[ TENTANG LAYANAN KAMI ]',
                ],
                'about_title' => [
                    'en' => 'One-Stop Travel Mobility & Island Tours',
                    'id' => 'Kenyamanan Akses Lewat Satu Pintu',
                ],
                'about_body' => [
                    'en' => "Based in Kuta Mandalika, Ocean Lombok Tour offers seamless vehicle rentals and curated tours throughout Lombok Island.\n\nIn addition to our dedicated fleet, we work closely with verified local drivers, boat operators, and licensed trekking guides. Simply get in touch with us, and we will tailor your transport with warm Sasak hospitality, transparency, and accountability.",
                    'id' => "Berbasis di Kuta Mandalika, Ocean Lombok Tour hadir memberikan kemudahan bagi wisatawan yang membutuhkan armada kendaraan dan tur terpercaya di seluruh Lombok.\n\nSelain menyediakan unit sendiri, kami juga menjalin kerja sama erat dengan rekanan penyedia transportasi serta kawan-kawan pemandu di lapangan. Cukup hubungi kami, dan kebutuhan mobilitas Anda akan kami bantu atur dengan ramah, jujur, dan bertanggung jawab.",
                ],
                'cta_title' => [
                    'en' => 'Plan Your Unforgettable Lombok Journey With Us',
                    'id' => 'Rencanakan Perjalanan Anda Bersama Ocean Lombok Tour',
                ],
                'cta_description' => [
                    'en' => 'Tell us about your Lombok holiday plans. Our local team in Kuta Mandalika is ready to recommend the ideal vehicles and tailored itineraries for you.',
                    'id' => 'Ceritakan rencana kunjungan Anda di Lombok. Tim operasional kami di Kuta Mandalika siap membantu menyesuaikan pilihan armada kendaraan dan pemandu yang tepat.',
                ],
                'cta_button_text' => [
                    'en' => 'Start Chat on WhatsApp',
                    'id' => 'Mulai Diskusi via WhatsApp',
                ],
                'cta_whatsapp_message' => [
                    'en' => 'Hello Ocean Lombok Tour, I am planning a trip to Lombok and would love recommendations on transport / tour packages.',
                    'id' => 'Halo Ocean Lombok Tour, saya sedang merencanakan trip ke Lombok dan ingin konsultasi rekomendasi sewa kendaraan / tur.',
                ],
                'meta_title' => [
                    'en' => 'Ocean Lombok Tour & Transport — Scooter Rental & Private Tours Kuta Mandalika',
                    'id' => 'Ocean Lombok Tour & Transport — Rental Motor, Mobil & Tur Kuta Mandalika',
                ],
                'meta_description' => [
                    'en' => 'Trusted motorbike rentals, private car transfers from Lombok Airport (LOP), and custom tour packages to Mount Rinjani, Kuta Mandalika, and the Gili Islands.',
                    'id' => 'Rental motor terpercaya, carter mobil & antar-jemput Bandara Lombok (LOP), serta paket tur privat Gunung Rinjani, Kuta Mandalika, dan 3 Gili.',
                ],
            ]
        );
    }
}
