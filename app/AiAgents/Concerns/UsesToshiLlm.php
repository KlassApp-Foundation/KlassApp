<?php

namespace App\AiAgents\Concerns;

use App\AiAgents\ToshiLlm;

trait UsesToshiLlm
{
    public function provider(): string
    {
        return ToshiLlm::provider();
    }

    public function model(): string
    {
        return ToshiLlm::model();
    }
}
