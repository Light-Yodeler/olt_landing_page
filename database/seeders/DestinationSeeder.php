<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;

class DestinationSeeder extends Seeder
{
    public function run(): void
    {
        $destinations = [
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAEPTXC9O8h9EsZ_ST22bbdSIfcbmQYFdOJh8ViGYSfuWyjE88UPvxk25GFF7nX9r6oUvuvQANDQLlw-XYn0MqZyWdMeH4gSwWjhqmLYlYcjRuRogtuJsPR0UWRzDLY1DeAHWbI9USESx_w8NcwW1Mkn0NtGMwxAVyHJB_QMLabV4OJ2mACexgbI1G9_WHXMIVG8O84Llh92MGflJbSLa1PPS0uTll47cZxxHajoQl8',
                'category_badge' => ['en' => 'Trekking & Mountain', 'id' => 'Trekking & Gunung'],
                'name' => ['en' => 'Mount Rinjani & Segara Anak', 'id' => 'Gunung Rinjani & Segara Anak'],
                'description' => [
                    'en' => "Indonesia's second-highest volcano offering breathtaking views of Segara Anak crater lake and Mount Baru Jari. Official routes via Senaru and Sembalun.",
                    'id' => 'Puncak gunung tertinggi kedua di Indonesia dengan panorama kawah Segara Anak dan Gunung Baru Jari yang megah. Jalur resmi via Senaru & Sembalun.',
                ],
                'location' => ['en' => 'East & North Lombok', 'id' => 'Lombok Timur & Utara'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I am interested in visiting Mount Rinjani. Please share the itinerary options.',
                    'id' => 'Halo OLT, saya tertarik berkunjung ke Gunung Rinjani. Mohon info pilihan jalurnya.',
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDCTP6H-YPV18szfGq9W0J-c1voSK35IwyOAtGbYcHCZ9xkB78tUq4FAzvLUypx7FA3Bj_BZVxJM7v3ZKHaN8nyCYjjyCwy-jIRtgK9Ja4KP8BmgbG_wD_iMXFXkXpNS647fGyyJE5A9bA52JhC1ncFLoA3orfkr3bIm0N4nBtVhb98XY9I9RufiYtt3Jj-KhSU8yyq8JdPqBsb9pFrzFEhAQO_hrZZHvfiZSWATinP',
                'category_badge' => ['en' => 'South Coast', 'id' => 'Pesisir Selatan'],
                'name' => ['en' => 'Kuta Mandalika & Merese Hill', 'id' => 'Kuta Mandalika & Bukit Merese'],
                'description' => [
                    'en' => 'Pristine pepper-sand coastlines, dramatic sunset cliff views at Merese Hill, and serene turquoise waters at Tanjung Aan Bay.',
                    'id' => 'Hamparan pantai pasir merica, garis tebing Bukit Merese untuk menikmati matahari terbenam, dan perairan tenang Tanjung Aan yang jernih.',
                ],
                'location' => ['en' => 'Central Lombok', 'id' => 'Lombok Tengah'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like to rent a scooter / car for exploring Kuta Mandalika and Merese Hill.',
                    'id' => 'Halo OLT, saya ingin sewa motor untuk keliling pantai Kuta Mandalika dan Bukit Merese.',
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuC3RirJ2jvqqMhLO83GwsMPOgDlEZ69zLw21RGryRzr1H_228GjK0bjEw4SWBmsuNc5MVNhq50tpzn6ndnHPlxqakKnmqwDZQ5dcu03Q1C_G5zg8UoWM8E3BHohTB7k7o2nwLmSLFa_oaRLlEt8RsSImB5nhzBlDqbgo4h-Sxtfj3PSQTLHkExKyRrWxJeU87GMmLA1rbAhoxYIGjqtQgQ0PNnwS0Ss_GRk2-F9tVEN',
                'category_badge' => ['en' => 'Jungle & Geopark', 'id' => 'Rimba & Geopark'],
                'name' => ['en' => 'Tiu Kelep & Sendang Gile', 'id' => 'Air Terjun Tiu Kelep & Sendang Gile'],
                'description' => [
                    'en' => 'Trek through lush rainforests and refreshing river crossings at the foot of Mount Rinjani to reach majestic cascading waterfalls.',
                    'id' => 'Menyusuri trek hutan tropis dan aliran sungai segar di kaki Gunung Rinjani menuju tirai air terjun alami dengan hembusan angin sejuk.',
                ],
                'location' => ['en' => 'Senaru, North Lombok', 'id' => 'Senaru, Lombok Utara'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I am interested in a guided day tour to Tiu Kelep Waterfall in Senaru.',
                    'id' => 'Halo OLT, saya tertarik tur pemandu ke Air Terjun Tiu Kelep Senaru.',
                ],
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCUBURHQPO5tzrPc1R7lEJzL9lHg51Ku6lOrzgZGexBzTsZpS9-4bONK_rtTLYcLqf85tG2n4BpZdoLvROid2Sr_fofacQfuCTcSNli4Qp2M4krihG2K0W3KS9hoScTKTspfA2xBXq4Yz7Y2NV_ilcb2JsJaR_JDTr4mAla8FWG-DuXEakfZvJh-AjOnZ2niiE7HYex-2QnDI8l4xdz0Q-3K9T9q6h99pbx2z02yanO',
                'category_badge' => ['en' => 'Surf Lessons & Trips', 'id' => 'Kelas & Trip Selancar'],
                'name' => ['en' => 'Selong Belanak & Gerupuk Bay', 'id' => 'Selong Belanak & Teluk Gerupuk'],
                'description' => [
                    'en' => "Lombok's prime surfing destinations. Selong Belanak's gentle sandy beach is ideal for beginners, while Gerupuk offers world-class reef breaks for intermediate and advanced riders.",
                    'id' => 'Spot selancar terbaik di Lombok Selatan. Garis pantai berpasir landai Selong Belanak ideal untuk belajar, sedangkan Gerupuk terkenal dengan ombak point break bagi peselancar lanjut.',
                ],
                'location' => ['en' => 'West & Central Lombok', 'id' => 'Lombok Barat & Tengah'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like to book beginner surf lessons at Selong Belanak / Gerupuk.',
                    'id' => 'Halo OLT, saya ingin ambil kelas surfing pemula di Selong Belanak / Gerupuk.',
                ],
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDVBW05snmoHyLWGzCRl4vO9cJUs_pKnYTGfoJ0Zs1kKFLz-8483McLwC3fE5WIKz7fMfG7d7UNtIMXnyLWrRop74T5b-n9jxpy8Vl7GwAv4aB6iS0cQR8MYrRvMiTcPob1GXQcIRmz6b4XJzXiS3dl8cYYoKlUZ5T_eYqbktvxsyv3HbEmgHMyUiIjK48TOsiYBzUs75KTnF5_1NVwipWFv9tzW9clyHIfJHeoGT0p',
                'category_badge' => ['en' => 'Islands & Coral Reefs', 'id' => 'Pulau & Terumbu Karang'],
                'name' => ['en' => 'Gili Trawangan, Meno & Air', 'id' => 'Gili Trawangan, Meno & Air'],
                'description' => [
                    'en' => 'Experience motor-free tropical islands, swim alongside green sea turtles, and explore the iconic underwater statues off Gili Meno.',
                    'id' => 'Menikmati pulau bebas kendaraan bermotor, berenang bersama penyu hijau di terumbu karang alami, serta mengunjungi situs patung bawah laut di Gili Meno.',
                ],
                'location' => ['en' => 'Pemenang, North Lombok', 'id' => 'Pemenang, Lombok Utara'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like details about boat transfers and private snorkeling in the 3 Gilis.',
                    'id' => 'Halo OLT, saya ingin info penyeberangan dan snorkeling 3 Gili.',
                ],
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'image_path' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBQvFu4pQTW8beBWegURd0sdpIIxUrwWPq-qLdXRaOKEZ_Tecbd94fhEP2iADlMSHrLd5FKDthrRmkx3BxXo1SipAihi4FqeKMrX3rAgV-jw2QklSE5DHINA1G5IftP3_UgToGNfl8Ewjr9gaVMY9ku3mYIJ50I_kHe7MFPBnbyw9eorLISAHyLGk1M7zDFc6H8qxhPiUC_Pf8tK_zxJ7megk9WLRcn0VnWm5NzLtr7',
                'category_badge' => ['en' => 'Sasak Culture & Heritage', 'id' => 'Budaya & Tradisi Sasak'],
                'name' => ['en' => 'Sade Village & Sukarara Weaving', 'id' => 'Desa Adat Sade & Tenun Sukarara'],
                'description' => [
                    'en' => 'Witness centuries-old Sasak architecture preserved in Sade village and learn traditional Songket hand-weaving techniques from master artisans in Sukarara.',
                    'id' => 'Menyaksikan arsitektur rumah tradisional suku Sasak yang lestari selama berabad-abad dan belajar proses tenun songket khas Lombok dari para pengrajin lokal.',
                ],
                'location' => ['en' => 'Pujut, Central Lombok', 'id' => 'Pujut, Lombok Tengah'],
                'button_text' => ['en' => 'Inquire Tour →', 'id' => 'Tanya Tur →'],
                'whatsapp_message' => [
                    'en' => 'Hello OLT, I would like to include Sade Traditional Village and Sukarara in my itinerary.',
                    'id' => 'Halo OLT, saya ingin rute singgah ke Desa Adat Sade dan Sukarara.',
                ],
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($destinations as $destination) {
            Destination::create($destination);
        }
    }
}
