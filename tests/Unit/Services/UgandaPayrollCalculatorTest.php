<?php

namespace Tests\Unit\Services;

use App\Services\UgandaPayrollCalculator;
use Carbon\Carbon;
use Tests\TestCase;

class UgandaPayrollCalculatorTest extends TestCase
{
    /** @test */
    public function it_returns_zero_paye_below_threshold()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(200000);
        $this->assertEquals(0, $tax);
    }

    /** @test */
    public function it_returns_zero_paye_at_threshold_boundary()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(235000);
        $this->assertEquals(0, $tax);
    }

    /** @test */
    public function it_calculates_paye_in_first_bracket()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(300000);
        // 235,001 to 300,000 = 64,999 @ 10% = 6,500 (rounded)
        $this->assertEquals(6500, $tax);
    }

    /** @test */
    public function it_calculates_paye_at_second_bracket_boundary()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(335000);
        // Band 1: 235,001-335,000 = 100,000 @ 10% = 10,000
        $this->assertEquals(10000, $tax);
    }

    /** @test */
    public function it_calculates_paye_in_third_bracket()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(400000);
        // Band 1: 235,001-335,000 = 100,000 @ 10% = 10,000
        // Band 2: 335,001-400,000 = 65,000 @ 20% = 13,000
        // Total = 23,000
        $this->assertEquals(23000, $tax);
    }

    /** @test */
    public function it_calculates_paye_in_fourth_bracket()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(1000000);
        // Band 1: 100,000 @ 10% = 10,000
        // Band 2: 75,000 @ 20% = 15,000 (cumulative: 25,000)
        // Band 3: 410,001-1,000,000 = 590,000 @ 30% = 177,000
        // Total = 202,000
        $this->assertEquals(202000, $tax);
    }

    /** @test */
    public function it_calculates_paye_in_top_bracket()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(15000000);
        // Cumulative up to 10M: 2,902,000
        // Excess: 5,000,000 @ 40% = 2,000,000
        // Total = 4,902,000
        $this->assertEquals(4902000, $tax);
    }

    /** @test */
    public function it_handles_exact_bracket_boundary_between_three_and_four()
    {
        $tax = UgandaPayrollCalculator::calculatePAYE(410000);
        // Band 1: 100k @ 10% = 10,000
        // Band 2: 75k @ 20% = 15,000
        // Total = 25,000
        $this->assertEquals(25000, $tax);
    }

    /** @test */
    public function it_calculates_nssf_employee_contribution()
    {
        $nssf = UgandaPayrollCalculator::calculateNSSF(1000000);
        $this->assertEquals(50000, $nssf); // 5% of 1,000,000
    }

    /** @test */
    public function it_caps_nssf_at_max_insurable_wage()
    {
        $nssf = UgandaPayrollCalculator::calculateNSSF(10000000);
        // Config cap = 5,000,000, so 5% of 5M = 250,000
        $this->assertEquals(250000, $nssf);
    }

    /** @test */
    public function it_calculates_employer_nssf()
    {
        $nssf = UgandaPayrollCalculator::calculateEmployerNSSF(1000000);
        $this->assertEquals(100000, $nssf); // 10% of 1,000,000
    }

    /** @test */
    public function it_caps_employer_nssf_at_max_insurable_wage()
    {
        $nssf = UgandaPayrollCalculator::calculateEmployerNSSF(10000000);
        $this->assertEquals(500000, $nssf); // 10% of 5M = 500,000
    }

    /** @test */
    public function lst_is_zero_outside_applicable_months()
    {
        $lst = UgandaPayrollCalculator::calculateLST(1000000, Carbon::parse('2026-06-15'));
        $this->assertEquals(0, $lst);
    }

    /** @test */
    public function lst_is_applicable_in_july()
    {
        $lst = UgandaPayrollCalculator::calculateLST(1000000, Carbon::parse('2026-07-15'));
        // 1,000,000 falls in the 500,001–1,000,000 band (20,000)
        $this->assertEquals(20000, $lst);
    }

    /** @test */
    public function lst_is_applicable_in_october()
    {
        $lst = UgandaPayrollCalculator::calculateLST(1000000, Carbon::parse('2026-10-15'));
        $this->assertEquals(20000, $lst);
    }

    /** @test */
    public function lst_uses_highest_band_for_high_income()
    {
        $lst = UgandaPayrollCalculator::calculateLST(2000000, Carbon::parse('2026-08-15'));
        $this->assertEquals(30000, $lst);
    }

    /** @test */
    public function lst_returns_zero_for_low_income()
    {
        $lst = UgandaPayrollCalculator::calculateLST(150000, Carbon::parse('2026-08-15'));
        $this->assertEquals(0, $lst);
    }

    /** @test */
    public function lst_uses_middle_band_for_mid_income()
    {
        $lst = UgandaPayrollCalculator::calculateLST(600000, Carbon::parse('2026-08-15'));
        $this->assertEquals(20000, $lst);
    }

    /** @test */
    public function compute_all_returns_expected_structure()
    {
        $result = UgandaPayrollCalculator::computeAll(
            1000000,
            Carbon::parse('2026-08-15')
        );

        $this->assertArrayHasKey('gross', $result);
        $this->assertArrayHasKey('paye', $result);
        $this->assertArrayHasKey('nssf_employee', $result);
        $this->assertArrayHasKey('nssf_employer', $result);
        $this->assertArrayHasKey('lst', $result);
        $this->assertArrayHasKey('total_deductions', $result);
        $this->assertArrayHasKey('net_pay', $result);

        $this->assertEquals(1000000, $result['gross']);
        $this->assertEquals(202000, $result['paye']);
        $this->assertEquals(50000, $result['nssf_employee']);
        $this->assertEquals(100000, $result['nssf_employer']);
        $this->assertEquals(20000, $result['lst']);

        // net_pay = gross - (paye + nssf_employee + lst) = 1,000,000 - 202,000 - 50,000 - 20,000
        $this->assertEquals(728000, $result['net_pay']);
    }

    /** @test */
    public function compute_all_returns_zero_lst_in_non_lst_month()
    {
        $result = UgandaPayrollCalculator::computeAll(
            1000000,
            Carbon::parse('2026-06-15')
        );

        $this->assertEquals(0, $result['lst']);
        // net_pay = 1,000,000 - 202,000 - 50,000 - 0 = 748,000
        $this->assertEquals(748000, $result['net_pay']);
    }
}
