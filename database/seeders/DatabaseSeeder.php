<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            SiteSettingSeeder::class,
            HeroSlideSeeder::class,
            ServiceSeeder::class,
            DestinationSeeder::class,
            StandardSeeder::class,
            FaqSeeder::class,
        ]);
    }
}
