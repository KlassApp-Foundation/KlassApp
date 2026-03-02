<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

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

    $country = DB::table("countries")->where("name", "Uganda")->first();
    $state = DB::table("states")->where("name", "UG")->first();

    $districts = [
    'Kampala', 'Kabale', 'Abim', 'Adjumani', 'Agago', 'Alebtong', 'Amolatar', 'Amudat', 'Amuria', 'Amuru',
    'Apac', 'Arua', 'Budaka', 'Bududa', 'Bugiri', 'Bugweri', 'Buhweju', 'Buikwe', 'Bukedea', 'Bukomansimbi',
    'Bukwo','Bulambuli','Buliisa','Bundibugyo','Bunyangabu','Bushenyi','Busia','Butaleja','Butambala','Butebo',
    'Buvuma', 'Buyende', 'Dokolo', 'Gomba', 'Gulu', 'Hoima', 'Ibanda', 'Iganga', 'Isingiro', 'Jinja',
    'Kaabong','Kabale','Kabarole','Kaberamaido','Kagadi','Kakumiro','Kalangala','Kaliro','Kalungu','Kampala',
    'Kamuli','Kamwenge','Kanungu','Kapchorwa','Kapelebyong','Kasanda','Kasese','Katakwi','Kayunga','Kazo',
    'Kibaale','Kiboga','Kibuku','Kikuube','Kiruhura','Kiryandongo','Kisoro','Kitagwenda','Kitgum','Koboko',
    'Kole', 'Kotido', 'Kumi', 'Kwania', 'Kween', 'Kyankwanzi', 'Kyegegwa', 'Kyenjojo', 'Kyotera', 'Lamwo',
    'Lira','Luuka','Luwero','Lwengo','Lyantonde','Madi-Okollo','Manafwa','Maracha','Masaka','Masindi',
    'Mayuge','Mbale','Mbarara','Mitooma','Mityana','Moroto','Moyo','Mpigi','Mubende','Mukono',
    'Nabilatuk','Nakapiripirit','Nakaseke','Nakasongola','Namayingo','Namisindwa','Namutumba','Napak','Nebbi','Ngora','Ntoroko','Ntungamo','Nwoya','Obongi','Omoro','Otuke','Oyam','Pader','Pakwach','Pallisa','Rakai', 'Rubanda', 'Rubirizi', 'Rukiga', 'Rukungiri', 'Rwampara', 'Serere', 'Sheema', 'Sironko', 'Soroti', 'Tororo',
    'Wakiso', 'Yumbe', 'Zombo'
    ];
    foreach ($districts as $district) {
        DB::table(table: 'cities')->insert([
            'country_id' => $country->id,
            'state_id'   => $state->id,
            'name'       => $district,
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
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
        DB::table('cities')->insert([
            'country_id' => $country->id,
            'state_id'   => $state->id,
            'name'       => $city['name'],
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

}
}
