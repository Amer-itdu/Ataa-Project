<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateRegionSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Damascus' => [
                'Mezzeh',
                'Kafr Sousa',
                'Bab Touma',
                'Midan',
                'Qaboun',
                'Barzeh',
                'Dummar',
            ],
            'Aleppo' => [
                'Jamiliyah',
                'Furqan',
                'Shahbaa',
                'Sulaimaniyah',
                'Hamdaniyah',
                'Zahraa',
                'Salihin',
            ],
            'Homs' => [
                'Al-Waer',
                'Bab Hud',
                'Zahrawi',
                'Inshaat',
                'Karm Al-Zaytoun',
                'Khalidiyah',
                'Bab Al-Sebaa',
            ],
            'Latakia' => [
                'Al-Raml Al-Janoubi',
                'Al-Raml Al-Shamali',
                'Zeraa',
                'Saliba',
                'Sheikh Daher',
                'Al-Dalia',
                'Al-Qalaa',
            ],
            'Hama' => [
                'Al-Hader',
                'Al-Jarajima',
                'Al-Sabboura',
                'Kazo',
                'Al-Midan',
                'Al-Sharia',
                'Al-Janayen',
            ],
            'Daraa' => [
                'Daraa Al-Balad',
                'Daraa Al-Mahatta',
                'Tafas',
                'Inkhil',
                'Al-Sanamayn',
                'Nawa',
                'Jasim',
            ],
            'Idlib' => [
                'Saraqib',
                'Maarat Al-Numan',
                'Ariha',
                'Harim',
                'Jisr Al-Shughur',
                'Binnish',
                'Kafr Nabl',
            ],
        ];

        foreach ($data as $governorateName => $regions) {
            $governorate = Governorate::create(['name' => $governorateName]);

            foreach ($regions as $regionName) {
                $governorate->regions()->create(['name' => $regionName]);
            }
        }
    }
}