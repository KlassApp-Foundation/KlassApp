<?php

/**
 * Uganda Statutory Payroll Rates
 *
 * Source: Uganda Revenue Authority (URA) — PAYE brackets
 *         National Social Security Fund (NSSF) — contribution rates
 *         Local Government Act — Local Service Tax (LST) bands
 *
 * Updated: July 2026 (FY 2024/25 rates)
 *
 * Rate changes require updating THIS FILE only — no code changes needed.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | PAYE (Pay As You Earn) — Progressive Brackets
    |--------------------------------------------------------------------------
    |
    | Monthly taxable income brackets. Each entry:
    |   'from'    => lower bound (inclusive)
    |   'to'      => upper bound (inclusive), null = infinity
    |   'rate'    => marginal rate as decimal
    |   'cumulative_tax' => tax on all prior brackets (for fast computation)
    |
    */
    'paye' => [
        'brackets' => [
            ['from' => 0,          'to' => 235000,     'rate' => 0.0,  'cumulative_tax' => 0],
            ['from' => 235001,     'to' => 335000,     'rate' => 0.10, 'cumulative_tax' => 0],
            ['from' => 335001,     'to' => 410000,     'rate' => 0.20, 'cumulative_tax' => 10000],
            ['from' => 410001,     'to' => 10000000,   'rate' => 0.30, 'cumulative_tax' => 25000],
            ['from' => 10000001,   'to' => null,       'rate' => 0.40, 'cumulative_tax' => 2902000],
        ],
        // Personal relief (already factored into bracket structure above)
        'personal_relief_per_month' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | NSSF (National Social Security Fund)
    |--------------------------------------------------------------------------
    |
    | Employee and employer contribution rates as percentages.
    | max_monthly_wage: maximum gross salary subject to NSSF.
    |   Set to 0 for no cap.
    |
    */
    'nssf' => [
        'employee_rate'   => 0.05,  // 5% of gross
        'employer_rate'   => 0.10,  // 10% of gross
        'max_monthly_wage' => 5000000, // UGX 5,000,000 ceiling
    ],

    /*
    |--------------------------------------------------------------------------
    | LST (Local Service Tax)
    |--------------------------------------------------------------------------
    |
    | Flat monthly amounts based on income bands.
    | Only applicable July–October (the LST collection window).
    | Rates vary by district; Kampala rates used as default.
    |
    */
    'lst' => [
        'applicable_months' => [7, 8, 9, 10], // July through October
        'bands' => [
            ['from' => 0,        'to' => 200000,   'amount' => 0],
            ['from' => 200001,   'to' => 500000,   'amount' => 10000],
            ['from' => 500001,   'to' => 1000000,  'amount' => 20000],
            ['from' => 1000001,  'to' => null,      'amount' => 30000],
        ],
    ],

];
