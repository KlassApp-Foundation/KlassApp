<?php

namespace App\Support;

/**
 * Display names for the demo school rosters. Each school calls this with
 * its own student count so two students in one school never share a name.
 */
class DemoRosterNames
{
    public const FIRSTS = [
        'Aisha', 'Brian', 'Carol', 'Daphine', 'Eric', 'Faith', 'Godfrey', 'Hawa', 'Ivan', 'Joan',
        'Kevin', 'Lillian', 'Moses', 'Naomi', 'Oscar', 'Patience', 'Ronald', 'Sylvia', 'Timothy', 'Winnie',
        'Aaron', 'Betty', 'Chris', 'Doreen', 'Elias', 'Fiona', 'Gerald', 'Harriet', 'Isaac', 'Jesca',
        'Kato', 'Lydia', 'Martin', 'Norah', 'Patrick', 'Queen', 'Reagan', 'Stella', 'Tonny', 'Ursula',
        'Vincent', 'Winnie', 'Yasin', 'Zainah', 'Arnold', 'Brenda', 'Cyrus', 'Denis', 'Esther', 'Fred',
        'Grace', 'Henry', 'Irene', 'Joel', 'Kevin', 'Lorna', 'Michael', 'Nancy', 'Owen', 'Proscovia',
        'Rashid', 'Sonia', 'Trevor', 'Vivian', 'Wilson', 'Yvonne',
    ];

    public const LASTS = [
        'Nabukenya', 'Okello', 'Auma', 'Kigozi', 'Achen', 'Ssentamu', 'Nansubuga', 'Tumwine', 'Wandera', 'Achieng',
        'Kirabo', 'Tumusiime', 'Namuli', 'Ssekandi', 'Mukasa', 'Nakato', 'Otieno', 'Amooti', 'Byaruhanga', 'Kyeyune',
        'Nsubuga', 'Babirye', 'Kizza', 'Ndagire', 'Wasswa', 'Nalubega', 'Kato', 'Nabirye', 'Odongo', 'Amongin',
        'Mbabazi', 'Lubega', 'Namugga', 'Ochieng', 'Turyasingha', 'Kembabazi', 'Mugisha', 'Nassiwa', 'Balewa', 'Kawooya',
    ];

    /**
     * @return list<string>
     */
    public static function names(int $count): array
    {
        $names = [];
        $firstCount = count(self::FIRSTS);
        $lastCount = count(self::LASTS);

        for ($n = 1; $n <= $count; $n++) {
            $names[] = self::FIRSTS[($n * 7) % $firstCount].' '.self::LASTS[($n * 11) % $lastCount];
        }

        return $names;
    }
}
