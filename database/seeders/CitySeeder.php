<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    /**
     * A városok betöltése a data/cities.json fájlból.
     *
     * A településnevek, irányítószámok és megyék valódiak, a population
     * viszont demo adat (véletlenszerű), mert településenkénti hiteles
     * népességi adat nem állt rendelkezésre.
     */
    public function run(): void
    {
        $cities = json_decode(file_get_contents(__DIR__.'/data/cities.json'), true);

        foreach ($cities as &$city) {
            $city['population'] = Str::contains($city['name'], 'Budapest')
                ? random_int(15000, 70000)
                : random_int(50, 8000);
        }
        unset($city);

        foreach (array_chunk($cities, 500) as $chunk) {
            DB::table('cities')->insert($chunk);
        }
    }
}
