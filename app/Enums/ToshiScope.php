<?php

namespace App\Enums;

/**
 * Authorization scope for Toshi SDK availability checks.
 *
 * School  — existing per-school gate (school with toshi_enabled).
 * Platform — siteadmin (usergroup 1) + explicit platform capability flag.
 *
 * Do not confuse with ToshiActionService::getRoleCapabilities()['scope'],
 * which is advisory LLM context only — never an auth boundary.
 */
enum ToshiScope: string
{
    case School = 'school';
    case Platform = 'platform';
}
