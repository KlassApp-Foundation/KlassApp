<?php

namespace Database\Seeders;

// use DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
 public function run()
{
    $now = now();

    

    $ugandaDistricts = [
    'Central' => [
        'Buikwe', 'Bukomansimbi', 'Butambala', 'Buvuma', 'Gomba', 'Kalangala',
        'Kalungu', 'Kampala', 'Kasanda', 'Kayunga', 'Kiboga', 'Kyankwanzi',
        'Kyotera', 'Luweero', 'Lwengo', 'Lyantonde', 'Masaka', 'Mityana',
        'Mpigi', 'Mubende', 'Mukono', 'Nakaseke', 'Nakasongola', 'Rakai',
        'Sembabule', 'Wakiso'
    ],

    'Western' => [
        'Buhweju', 'Buliisa', 'Bundibugyo', 'Bunyangabu', 'Bushenyi', 'Hoima',
        'Ibanda', 'Isingiro', 'Kabale', 'Kabarole', 'Kagadi', 'Kakumiro',
        'Kamwenge', 'Kanungu', 'Kasese', 'Kibaale', 'Kikuube', 'Kiruhura',
        'Kisoro', 'Kitagwenda', 'Kyenjojo', 'Kyegegwa', 'Mbarara', 'Mitooma',
        'Ntoroko', 'Ntungamo', 'Rubanda', 'Rubirizi', 'Rukiga', 'Rukungiri',
        'Rwampara', 'Sheema'
    ],

    'Eastern' => [
        'Amuria', 'Budaka', 'Bududa', 'Bugiri', 'Bugweri', 'Bukedea', 'Bukwo',
        'Bulambuli', 'Butebo', 'Busia', 'Butaleja', 'Buyende', 'Iganga', 'Jinja',
        'Kaberamaido', 'Kaliro', 'Kamuli', 'Kapchorwa', 'Kapelebyong', 'Katakwi',
        'Kibuku', 'Kumi', 'Kween', 'Luuka', 'Manafwa', 'Mayuge', 'Mbale',
        'Namisindwa', 'Namayingo', 'Namutumba', 'Ngora', 'Pallisa', 'Serere',
        'Sironko', 'Soroti', 'Tororo'
    ],

    'Northern' => [
        'Abim', 'Adjumani', 'Agago', 'Alebtong', 'Amolatar', 'Amudat', 'Amuru',
        'Apac', 'Arua', 'Dokolo', 'Gulu', 'Kaabong', 'Koboko', 'Kole', 'Kotido',
        'Lamwo', 'Lira', 'Madi-Okollo', 'Maracha', 'Moroto', 'Moyo', 'Nabilatuk',
        'Nakapiripirit', 'Napak', 'Nebbi', 'Nwoya', 'Obongi', 'Omoro', 'Otuke',
        'Oyam', 'Pader', 'Pakwach', 'Yumbe', 'Zombo', 'Kwania', 'Karenga'
    ]
];
$country = DB::table("countries")->where("name", "Uganda")->first();

// foreach($ugandaDistricts['Northern'] as $dist){
// //    dd($region);
//     $region = DB::table("states")
//                    ->where("country_id", $country->id)
//                    ->where("name", "Northern")
//                    ->first();
//                     dd($region);
//     $conditions = [
//     'name' => $dist,
//     'country_id' => $country->id,
// ];

// $values = [
//     'state_id'   => $region->id,
//     'status'     => 1,
//     'created_at' => $now,
//     'updated_at' => $now,
// ];

// DB::table('cities')->updateOrInsert($conditions, $values);
// }
   
    foreach ($ugandaDistricts as $regionName => $districtList) {
         $region = DB::table("states")
                   ->where("country_id", $country->id)
                   ->where("name", $regionName)
                   ->first();

        foreach ($districtList as $districtName){
            if (!$region) {
                 $this->command->error("❌ Region NOT FOUND → Region: {$regionName}, District: {$districtName}, Country ID: {$country->id}");
                 continue;
           } 
           $conditions = ['name' => $districtName,
            'country_id' => $country->id
            ];
            $values = [
            'state_id'   => $region->id,
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];
            DB::table('cities')->updateOrInsert($conditions, $values);
        }
    }

    // Foreign Cities (continue state_id)
    $foreignCities = [
        ['country' => "Kenya", 'name' => 'Nairobi'],
        ['country' => "Tanzania", 'name' => 'Dodoma'],
        ['country' => "Rwanda", 'name' => 'Bujumbura'],
        ['country' => "Burundi", 'name' => 'Juba'],
        ['country' => "South Sudan", 'name' => 'Kinshasa'],
        ['country' => "DRC", 'name' => 'Johannesburg'],
        ['country' => "Nigeria", 'name' => 'Lagos'],
        ['country' => "Egypt", 'name' => 'Cairo'],
        ['country' => "Other", 'name' => 'Other'],
        
    ];
foreach ($foreignCities as $city) {
        $country = DB::table("countries")->where("name", $city["country"])->first();
        $state = DB::table("states")->where("id", $country->id)->first();
        DB::table('cities')->updateOrInsert(
            ['name'=> $city['name']],
             [
            'country_id' => $country->id,
            'state_id'   => $state->id,
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

}
}
