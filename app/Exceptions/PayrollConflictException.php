<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when an attempt is made to run payroll for a staff member
 * who already has a payroll record covering the same period.
 */
class PayrollConflictException extends Exception
{
    //
}
