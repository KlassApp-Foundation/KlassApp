<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * UgandaPayrollCalculator — Statutory payroll deductions for Uganda.
 *
 * Computes PAYE (progressive bracket), NSSF (flat %), and LST (banded flat).
 * All rate tables live in config/payroll_uganda.php so rate changes
 * don't require code changes — only config publishes.
 *
 * Every method is independently testable and side-effect-free.
 */
class UgandaPayrollCalculator
{
    /**
     * Calculate PAYE tax on a monthly gross salary.
     *
     * Uses URA progressive bracket rates from config.
     * Returns the tax amount in UGX (rounded to nearest whole shilling).
     */
    public static function calculatePAYE(float $grossMonthly): float
    {
        $brackets = config('payroll_uganda.paye.brackets', []);
        $tax = 0.0;

        foreach ($brackets as $bracket) {
            if ($grossMonthly < $bracket['from']) {
                break;
            }

            $bandTop = $bracket['to'] ?? PHP_FLOAT_MAX;
            $taxableInBand = min($grossMonthly, $bandTop) - ($bracket['from'] - 1);

            if ($taxableInBand > 0) {
                $tax += $taxableInBand * $bracket['rate'];
            }
        }

        return round($tax);
    }

    /**
     * Calculate NSSF employee contribution.
     *
     * 5% of gross salary, capped at the max insurable wage in config.
     * Returns the contribution amount in UGX.
     */
    public static function calculateNSSF(float $grossMonthly): float
    {
        $rate = config('payroll_uganda.nssf.employee_rate', 0.05);
        $cap = (float) config('payroll_uganda.nssf.max_monthly_wage', 0);

        $wageBase = $cap > 0 ? min($grossMonthly, $cap) : $grossMonthly;

        return round($wageBase * $rate);
    }

    /**
     * Calculate NSSF employer contribution.
     *
     * 10% of gross salary, same wage cap as employee.
     * Separate method so payroll reports can show employer cost.
     */
    public static function calculateEmployerNSSF(float $grossMonthly): float
    {
        $rate = config('payroll_uganda.nssf.employer_rate', 0.10);
        $cap = (float) config('payroll_uganda.nssf.max_monthly_wage', 0);

        $wageBase = $cap > 0 ? min($grossMonthly, $cap) : $grossMonthly;

        return round($wageBase * $rate);
    }

    /**
     * Calculate Local Service Tax for a given pay period.
     *
     * LST is a flat amount per income band, collected July–October only.
     * Returns 0 if the pay period falls outside the LST window.
     */
    public static function calculateLST(float $grossMonthly, Carbon $payPeriod): float
    {
        $applicableMonths = config('payroll_uganda.lst.applicable_months', [7, 8, 9, 10]);

        if (!in_array((int) $payPeriod->format('n'), $applicableMonths)) {
            return 0.0;
        }

        $bands = config('payroll_uganda.lst.bands', []);

        foreach ($bands as $band) {
            if ($grossMonthly >= $band['from']
                && ($band['to'] === null || $grossMonthly <= $band['to'])) {
                return (float) $band['amount'];
            }
        }

        return 0.0;
    }

    /**
     * Compute all statutory deductions at once.
     *
     * Returns an associative array with keys:
     *   paye, nssf_employee, nssf_employer, lst, total_deductions
     */
    public static function computeAll(float $grossMonthly, Carbon $payPeriod): array
    {
        $paye = self::calculatePAYE($grossMonthly);
        $nssfEmployee = self::calculateNSSF($grossMonthly);
        $nssfEmployer = self::calculateEmployerNSSF($grossMonthly);
        $lst = self::calculateLST($grossMonthly, $payPeriod);

        $totalDeductions = $paye + $nssfEmployee + $lst;

        return [
            'gross'            => round($grossMonthly),
            'paye'             => $paye,
            'nssf_employee'    => $nssfEmployee,
            'nssf_employer'    => $nssfEmployer,
            'lst'              => $lst,
            'total_deductions' => $totalDeductions,
            'net_pay'          => round($grossMonthly) - $totalDeductions,
        ];
    }
}
