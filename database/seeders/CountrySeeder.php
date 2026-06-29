<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Country;
use Illuminate\Support\Facades\Schema;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        
        // country table
        Country::truncate();
        $json = json_decode(file_get_contents(database_path('seeders/data/countries.json')), true);
        foreach ($json as $row) {
            Country::create([
                'id'              => $row['id'],
                'code'            => $row['code'],
                'name'            => $row['name'],
                'capital'         => $row['capital'] ?? '',
                'continent'       => $row['continent'],
                'continent_code'  => $row['continent_code'],
                'phone'           => $row['phone'],
                'currency'        => $row['currency'],
                'symbol'          => $row['symbol'],
                'alpha_3'         => $row['alpha_3'],
                'latitude'          => $row['latitude'],
                'longitude'         => $row['longitude'],
                'created_at'      => $row['created_at'],
                'updated_at'      => $row['updated_at'],
            ]);
        }
    }
}
