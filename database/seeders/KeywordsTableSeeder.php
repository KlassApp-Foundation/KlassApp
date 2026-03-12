<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KeywordsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $keywords = [
            // PHP reserved keywords (mostly correct)
            ['id' => 1,  'name' => 'break'],
            ['id' => 2,  'name' => 'clone'],
            ['id' => 3,  'name' => 'die'],
            ['id' => 4,  'name' => 'empty'],
            ['id' => 5,  'name' => 'endswitch'],
            ['id' => 6,  'name' => 'final'],
            ['id' => 7,  'name' => 'global'],
            ['id' => 8,  'name' => 'include_once'],  // fixed typo: inlude_once → include_once
            ['id' => 9,  'name' => 'list'],
            ['id' => 10, 'name' => 'private'],
            ['id' => 11, 'name' => 'return'],
            ['id' => 12, 'name' => 'try'],
            ['id' => 13, 'name' => 'abstract'],
            ['id' => 14, 'name' => 'callable'],
            ['id' => 15, 'name' => 'const'],
            ['id' => 16, 'name' => 'do'],
            ['id' => 17, 'name' => 'enddeclare'],
            ['id' => 18, 'name' => 'endwhile'],
            ['id' => 19, 'name' => 'finally'],
            ['id' => 20, 'name' => 'goto'],
            ['id' => 21, 'name' => 'instanceof'],
            ['id' => 22, 'name' => 'namespace'],
            ['id' => 23, 'name' => 'xor'],
            ['id' => 24, 'name' => 'static'],
            ['id' => 25, 'name' => 'unset'],
            ['id' => 26, 'name' => 'yield'],
            ['id' => 27, 'name' => 'and'],
            ['id' => 28, 'name' => 'case'],
            ['id' => 29, 'name' => 'continue'],
            ['id' => 30, 'name' => 'echo'],
            ['id' => 31, 'name' => 'endfor'],
            ['id' => 32, 'name' => 'eval'],
            ['id' => 33, 'name' => 'for'],
            ['id' => 34, 'name' => 'if'],
            ['id' => 35, 'name' => 'insteadof'],
            ['id' => 36, 'name' => 'new'],
            ['id' => 37, 'name' => 'public'],
            ['id' => 38, 'name' => 'switch'],
            ['id' => 39, 'name' => 'use'],
            ['id' => 40, 'name' => 'yield from'],
            ['id' => 41, 'name' => 'array'],
            ['id' => 42, 'name' => 'catch'],
            ['id' => 43, 'name' => 'declare'],
            ['id' => 44, 'name' => 'else'],
            ['id' => 45, 'name' => 'endforeach'],
            ['id' => 46, 'name' => 'exit'],
            ['id' => 47, 'name' => 'foreach'],
            ['id' => 48, 'name' => 'implements'],
            ['id' => 49, 'name' => 'interface'],
            ['id' => 50, 'name' => 'or'],
            ['id' => 51, 'name' => 'require'],
            ['id' => 52, 'name' => 'throw'],
            ['id' => 53, 'name' => 'var'],
            ['id' => 54, 'name' => 'as'],
            ['id' => 55, 'name' => 'class'],
            ['id' => 56, 'name' => 'default'],
            ['id' => 57, 'name' => 'elseif'],
            ['id' => 58, 'name' => 'endif'],
            ['id' => 59, 'name' => 'extends'],
            ['id' => 60, 'name' => 'function'],
            ['id' => 61, 'name' => 'include'],
            ['id' => 62, 'name' => 'isset'],
            ['id' => 63, 'name' => 'print'],
            ['id' => 64, 'name' => 'require_once'],
            ['id' => 65, 'name' => 'trait'],
            ['id' => 66, 'name' => 'while'],

            // Dev tools / common terms (kept as-is, maybe for filtering/blocking?)
            ['id' => 67, 'name' => 'FileZilla'],
            ['id' => 68, 'name' => 'Sublime'],
            ['id' => 69, 'name' => 'Slack'],
            ['id' => 70, 'name' => 'google'],
            ['id' => 71, 'name' => 'gmail'],
            ['id' => 72, 'name' => 'yahoo'],
            ['id' => 73, 'name' => 'Mailtrap'],
            ['id' => 74, 'name' => 'cPanel'],
            ['id' => 75, 'name' => 'Laragon'],
            ['id' => 76, 'name' => 'Postman'],
            ['id' => 77, 'name' => 'MySQL'],
        ];

        foreach ($keywords as $keyword) {
            DB::table('keywords')->updateOrInsert(
                ['id' => $keyword['id']],
                [
                    'name'       => $keyword['name'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}