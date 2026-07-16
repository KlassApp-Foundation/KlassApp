<?php

namespace App\Exceptions;

use Exception;

class MarksLockedException extends Exception
{
    public function __construct(?string $deadline = null)
    {
        $message = 'Marks are locked and cannot be edited.';

        if ($deadline) {
            $message = "Marks are locked and cannot be edited. The deadline was {$deadline}. Contact the admin if you need them reopened.";
        }

        parent::__construct($message);
    }

    public function render(): \Illuminate\Http\RedirectResponse
    {
        return redirect()->back()->withErrors([
            'marks_locked' => $this->getMessage(),
        ]);
    }
}
