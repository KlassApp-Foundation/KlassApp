<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class WelcomeController extends Controller
{
    /**
     * Display a the welcome page from the View File
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
{
    $plans = Plan::query()
        ->where('is_active', true)
        ->orderBy('amount')
        ->get();
// dd($plans);
    return view('landing', compact('plans'));
}
}