<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => [
                    'en' => 'What are the requirements for renting a scooter or motorbike?',
                    'id' => 'Bagaimana persyaratan sewa sepeda motor di Ocean Lombok Tour?',
                ],
                'answer' => [
                    'en' => 'Simply provide a valid ID photo (Passport or National ID Card), valid driver\'s license, and proof of your flight ticket or accommodation booking in Lombok. We do not withhold your original physical documents.',
                    'id' => 'Cukup sediakan foto identitas resmi (KTP atau Paspor), foto SIM yang masih berlaku, serta bukti tiket penerbangan atau reservasi penginapan di Lombok. Kami tidak menahan kartu identitas fisik asli tamu.',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'question' => [
                    'en' => 'Do you provide airport transfer to/from Lombok Airport (LOP)?',
                    'id' => 'Apakah tersedia layanan antar-jemput ke Bandara Lombok (LOP)?',
                ],
                'answer' => [
                    'en' => 'Yes, we provide private car transfers to and from Lombok International Airport (LOP) scheduled around your flights. For scooter rentals, vehicle delivery is provided directly to your hotel in the Kuta Mandalika area.',
                    'id' => 'Ya, kami melayani antar-jemput mobil dari dan menuju Bandara Internasional Lombok (LOP) sesuai jadwal penerbangan Anda. Untuk unit sewa motor, serah-terima kami layani langsung di area Kuta Mandalika dan penginapan sekitarnya.',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'question' => [
                    'en' => 'Is the Mount Rinjani trek suitable for beginner hikers?',
                    'id' => 'Apakah rute pendakian Rinjani cocok untuk pendaki pemula?',
                ],
                'answer' => [
                    'en' => 'Yes! We offer beginner-friendly itineraries up to the Senaru Crater Rim (Plawangan Senaru) which offers spectacular views of the caldera lake. Our porters and mountain guides will take care of camp logistics and equipment.',
                    'id' => 'Sangat bisa. Kami menyediakan paket ramah pemula dengan rute menuju Pelawangan Senaru yang memiliki panorama kawah megah. Kru pemandu dan porter akan membantu logistik dan perlengkapan.',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'question' => [
                    'en' => 'How do I book private boat charters or surf coaching sessions?',
                    'id' => 'Bagaimana cara memesan paket carter kapal atau selancar?',
                ],
                'answer' => [
                    'en' => 'Simply message our official WhatsApp with your preferred dates and group size. We will check real-time sea tides and private boat schedules to recommend the best time slots.',
                    'id' => 'Cukup hubungi WhatsApp resmi kami dengan menyebutkan tanggal rencana dan jumlah peserta. Kami akan menyiapkan jadwal terbaik menyesuaikan kondisi pasang surut air laut dan ketersediaan perahu privat.',
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}
