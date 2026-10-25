<?php

/*
| Calling codes for phone fields, keyed by country name.
|
| Local numbers are prefixed with the calling code for the invite's or
| school's country. The countries.tel_prefix column is not used for names
| listed here: that column still carries placeholder prefixes for several
| rows. An admin-created country that is not listed falls back to its
| tel_prefix.
*/

// FLAGGED(product-decision): calling codes are a fixed list, with Uganda's 256/9-digit rule inside it. see docs/onboarding-engine-plan.md
return [
    'aliases' => [
        'ug' => 'Uganda',
        'ke' => 'Kenya',
        'tz' => 'Tanzania',
        'rw' => 'Rwanda',
        'uk' => 'United Kingdom',
        'us' => 'United States',
        'usa' => 'United States',
        'uae' => 'United Arab Emirates',
    ],

    // code: ITU calling code. min/max: national significant number length.
    'countries' => [
        'Argentina' => ['code' => '54', 'min' => 10, 'max' => 10],
        'Australia' => ['code' => '61', 'min' => 9, 'max' => 9],
        'Bangladesh' => ['code' => '880', 'min' => 10, 'max' => 10],
        'Brazil' => ['code' => '55', 'min' => 10, 'max' => 11],
        'Burundi' => ['code' => '257', 'min' => 8, 'max' => 8],
        'Canada' => ['code' => '1', 'min' => 10, 'max' => 10],
        'Chile' => ['code' => '56', 'min' => 9, 'max' => 9],
        'China' => ['code' => '86', 'min' => 11, 'max' => 11],
        'Colombia' => ['code' => '57', 'min' => 10, 'max' => 10],
        'DRC' => ['code' => '243', 'min' => 9, 'max' => 9],
        'Egypt' => ['code' => '20', 'min' => 10, 'max' => 10],
        'France' => ['code' => '33', 'min' => 9, 'max' => 9],
        'Germany' => ['code' => '49', 'min' => 10, 'max' => 11],
        'Ghana' => ['code' => '233', 'min' => 9, 'max' => 9],
        'India' => ['code' => '91', 'min' => 10, 'max' => 10],
        'Indonesia' => ['code' => '62', 'min' => 9, 'max' => 12],
        'Ireland' => ['code' => '353', 'min' => 9, 'max' => 9],
        'Italy' => ['code' => '39', 'min' => 9, 'max' => 10],
        'Japan' => ['code' => '81', 'min' => 10, 'max' => 10],
        'Kenya' => ['code' => '254', 'min' => 9, 'max' => 9],
        'Malaysia' => ['code' => '60', 'min' => 9, 'max' => 10],
        'Mexico' => ['code' => '52', 'min' => 10, 'max' => 10],
        'Netherlands' => ['code' => '31', 'min' => 9, 'max' => 9],
        'New Zealand' => ['code' => '64', 'min' => 8, 'max' => 10],
        'Nigeria' => ['code' => '234', 'min' => 10, 'max' => 10],
        'Pakistan' => ['code' => '92', 'min' => 10, 'max' => 10],
        'Philippines' => ['code' => '63', 'min' => 10, 'max' => 10],
        'Poland' => ['code' => '48', 'min' => 9, 'max' => 9],
        'Portugal' => ['code' => '351', 'min' => 9, 'max' => 9],
        'Rwanda' => ['code' => '250', 'min' => 9, 'max' => 9],
        'Saudi Arabia' => ['code' => '966', 'min' => 9, 'max' => 9],
        'Singapore' => ['code' => '65', 'min' => 8, 'max' => 8],
        'South Africa' => ['code' => '27', 'min' => 9, 'max' => 9],
        'South Korea' => ['code' => '82', 'min' => 9, 'max' => 10],
        'South Sudan' => ['code' => '211', 'min' => 9, 'max' => 9],
        'Spain' => ['code' => '34', 'min' => 9, 'max' => 9],
        'Sweden' => ['code' => '46', 'min' => 9, 'max' => 9],
        'Tanzania' => ['code' => '255', 'min' => 9, 'max' => 9],
        'Thailand' => ['code' => '66', 'min' => 9, 'max' => 9],
        'Turkey' => ['code' => '90', 'min' => 10, 'max' => 10],
        'Uganda' => ['code' => '256', 'min' => 9, 'max' => 9],
        'Ukraine' => ['code' => '380', 'min' => 9, 'max' => 9],
        'United Arab Emirates' => ['code' => '971', 'min' => 9, 'max' => 9],
        'United Kingdom' => ['code' => '44', 'min' => 10, 'max' => 10],
        'United States' => ['code' => '1', 'min' => 10, 'max' => 10],
        'Vietnam' => ['code' => '84', 'min' => 9, 'max' => 9],
        'Zambia' => ['code' => '260', 'min' => 9, 'max' => 9],
    ],
];
