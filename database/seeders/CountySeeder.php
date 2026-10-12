<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountySeeder extends Seeder
{
    /** A megyék betöltése a data/counties.json fájlból. */
    public function run(): void
    {
        $counties = json_decode(file_get_contents(__DIR__.'/data/counties.json'), true);

        DB::table('counties')->insert($counties);
    }
}
