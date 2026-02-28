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

    $stateId = 1;

    foreach ($districts as $district) {
        DB::table('cities')->insert([
            'country_id' => 1,
            'state_id'   => $stateId++,
            'name'       => $district,
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // Foreign Cities (continue state_id)
    $foreignCities = [
        ['country_id' => 2, 'name' => 'Nairobi'],
        ['country_id' => 3, 'name' => 'Dodoma'],
        ['country_id' => 5, 'name' => 'Bujumbura'],
        ['country_id' => 6, 'name' => 'Juba'],
        ['country_id' => 7, 'name' => 'Kinshasa'],
        ['country_id' => 8, 'name' => 'Johannesburg'],
        ['country_id' => 9, 'name' => 'Lagos'],
        ['country_id' => 10, 'name' => 'Cairo'],
    ];

    foreach ($foreignCities as $city) {
        DB::table('cities')->insert([
            'country_id' => $city['country_id'],
            'state_id'   => $stateId++,
            'name'       => $city['name'],
            'status'     => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    // Final fallback option
    DB::table('cities')->insert([
        'country_id' => 11,
        'state_id'   => $stateId,
        'name'       => 'Other',
        'status'     => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}
}
