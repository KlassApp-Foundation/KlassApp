<?php

namespace App\Models\Concerns;

use App\Models\Approval;

trait HasApprovals
{
    public function approvals()
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function pendingApproval()
    {
        return $this->morphOne(Approval::class, 'approvable')
            ->where('state', 'App\\States\\Approval\\Pending');
    }

    public function latestApproval()
    {
        return $this->morphOne(Approval::class, 'approvable')
            ->latestOfMany();
    }

    public function scopeWhereApprovalState($query, string $stateClass)
    {
        return $query->whereHas('approvals', function ($q) use ($stateClass) {
            $q->where('state', $stateClass);
        });
    }
}
