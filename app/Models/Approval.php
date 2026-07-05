<?php

namespace App\Models;

use App\States\Approval\ApprovalState;
use App\States\Approval\Pending;
use App\Traits\NotifyOnStatusChange;
use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStates\HasStates;

class Approval extends Model
{
    use HasStates;
    use NotifyOnStatusChange;

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'state',
        'requested_by',
        'approved_by',
        'comments',
        'resolved_at',
    ];

    protected $casts = [
        'state' => \App\States\Approval\ApprovalState::class,
        'resolved_at' => 'datetime',
    ];

    protected function registerStates(): void
    {
        $this->addState('state', ApprovalState::class)
            ->default(Pending::class);
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
