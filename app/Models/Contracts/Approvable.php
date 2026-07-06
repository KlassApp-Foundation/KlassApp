<?php

namespace App\Models\Contracts;

interface Approvable
{
    public function approvals();

    public function pendingApproval();

    public function latestApproval();
}
