<?php

namespace App\AiAgents\Skills;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Attributes\MaxSteps;
use App\AiAgents\Tools\SetGradingScaleTool;
use App\AiAgents\Tools\ViewGradingScaleTool;
use App\AiAgents\Tools\SeedDefaultGradingTool;

#[MaxSteps(5)]
class GradingSkill implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You handle grading scale management. You can set grading scales for specific standards/classes, view existing grading scales, and seed default UNEB grading scales for all standards. The default UNEB scale is: A (80-100), B (70-79), C (55-69), D (40-54), E (0-39). Confirm with the user before modifying grading scales.';
    }

    public function tools(): iterable
    {
        return [
            new SetGradingScaleTool,
            new ViewGradingScaleTool,
            new SeedDefaultGradingTool,
        ];
    }
}
