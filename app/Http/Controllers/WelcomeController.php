<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers;

class WelcomeController extends Controller
{
    /**
     * Display the public landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        return view('landing-v2');
    }
}