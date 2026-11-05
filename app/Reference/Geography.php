<?php

namespace App\Reference;

/**
 * States and cities seeded as reference data.
 *
 * Uganda's states are the four regions. Every other country
 * has one state. Districts and foreign capitals are cities,
 * and each city is stored against the state of its country.
 * State names for the non-Uganda rows are the labels this
 * seeder has always used (they are not ISO codes).
 */
final class Geography
{
    /**
     * @return list<string>
     */
    public static function ugandaRegions(): array
    {
        return ['Central', 'Eastern', 'Northern', 'Western'];
    }

    /**
     * One state name per country other than Uganda.
     *
     * @return array<string, string>
     */
    public static function otherStateNames(): array
    {
        return [
            'Kenya' => 'KE',
            'Tanzania' => 'TZ',
            'Rwanda' => 'RW',
            'Burundi' => 'BU',
            'South Sudan' => 'SS',
            'DRC' => 'DR',
            'Nigeria' => 'NG',
            'Egypt' => 'EG',
            'Other' => 'OTHER',
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function ugandaDistricts(): array
    {
        return [
            'Central' => [
                'Buikwe', 'Bukomansimbi', 'Butambala', 'Buvuma', 'Gomba', 'Kalangala',
                'Kalungu', 'Kampala', 'Kasanda', 'Kayunga', 'Kiboga', 'Kyankwanzi',
                'Kyotera', 'Luweero', 'Lwengo', 'Lyantonde', 'Masaka', 'Mityana',
                'Mpigi', 'Mubende', 'Mukono', 'Nakaseke', 'Nakasongola', 'Rakai',
                'Sembabule', 'Wakiso',
            ],
            'Western' => [
                'Buhweju', 'Buliisa', 'Bundibugyo', 'Bunyangabu', 'Bushenyi', 'Hoima',
                'Ibanda', 'Isingiro', 'Kabale', 'Kabarole', 'Kagadi', 'Kakumiro',
                'Kamwenge', 'Kanungu', 'Kasese', 'Kibaale', 'Kikuube', 'Kiruhura',
                'Kisoro', 'Kitagwenda', 'Kyenjojo', 'Kyegegwa', 'Mbarara', 'Mitooma',
                'Ntoroko', 'Ntungamo', 'Rubanda', 'Rubirizi', 'Rukiga', 'Rukungiri',
                'Rwampara', 'Sheema',
            ],
            'Eastern' => [
                'Amuria', 'Budaka', 'Bududa', 'Bugiri', 'Bugweri', 'Bukedea', 'Bukwo',
                'Bulambuli', 'Butebo', 'Busia', 'Butaleja', 'Buyende', 'Iganga', 'Jinja',
                'Kaberamaido', 'Kaliro', 'Kamuli', 'Kapchorwa', 'Kapelebyong', 'Katakwi',
                'Kibuku', 'Kumi', 'Kween', 'Luuka', 'Manafwa', 'Mayuge', 'Mbale',
                'Namisindwa', 'Namayingo', 'Namutumba', 'Ngora', 'Pallisa', 'Serere',
                'Sironko', 'Soroti', 'Tororo',
            ],
            'Northern' => [
                'Abim', 'Adjumani', 'Agago', 'Alebtong', 'Amolatar', 'Amudat', 'Amuru',
                'Apac', 'Arua', 'Dokolo', 'Gulu', 'Kaabong', 'Koboko', 'Kole', 'Kotido',
                'Lamwo', 'Lira', 'Madi-Okollo', 'Maracha', 'Moroto', 'Moyo', 'Nabilatuk',
                'Nakapiripirit', 'Napak', 'Nebbi', 'Nwoya', 'Obongi', 'Omoro', 'Otuke',
                'Oyam', 'Pader', 'Pakwach', 'Yumbe', 'Zombo', 'Kwania', 'Karenga',
            ],
        ];
    }

    /**
     * @return list<array{country: string, name: string}>
     */
    public static function foreignCities(): array
    {
        return [
            ['country' => 'Kenya', 'name' => 'Nairobi'],
            ['country' => 'Tanzania', 'name' => 'Dodoma'],
            ['country' => 'Rwanda', 'name' => 'Kigali'],
            ['country' => 'Burundi', 'name' => 'Bujumbura'],
            ['country' => 'South Sudan', 'name' => 'Juba'],
            ['country' => 'DRC', 'name' => 'Kinshasa'],
            ['country' => 'Nigeria', 'name' => 'Lagos'],
            ['country' => 'Egypt', 'name' => 'Cairo'],
            ['country' => 'Other', 'name' => 'Other'],
        ];
    }

    public static function expectedStateCount(): int
    {
        return count(self::ugandaRegions()) + count(self::otherStateNames());
    }

    public static function expectedCityCount(): int
    {
        $districts = 0;
        foreach (self::ugandaDistricts() as $names) {
            $districts += count($names);
        }

        return $districts + count(self::foreignCities());
    }
}
