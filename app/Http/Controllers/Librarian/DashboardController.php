<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Librarian;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Dashboard;

class DashboardController extends Controller
{
    use Dashboard;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $librarian_id   =   Auth::id();
        $school_id      =   Auth::user()->school_id;
        $dashboard      =   $this->librarianDashboard( $school_id, $librarian_id );

        return view('/library/dashboard', [ 'dashboard' => $dashboard ]);
    }
}