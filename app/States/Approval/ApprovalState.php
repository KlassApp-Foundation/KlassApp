<?php

namespace App\States\Approval;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ApprovalState extends State
{
    abstract public function label(): string;

    abstract public function color(): string;

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Pending::class)
            ->allowTransition(Pending::class, Approved::class)
            ->allowTransition(Pending::class, Rejected::class);
    }
}
