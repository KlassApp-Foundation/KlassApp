<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\API\BookLending as BookLendingResource;
use App\Models\BookLending;

class LibraryActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       $lent=BookLending::with('book')->where('user_id',Auth::user()->id)->get();

       return view('/student/libraryactivity/index',['lent'=>$lent]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $lent = BookLending::with('book')->where('user_id',Auth::user()->id)->get();

        $lent = BookLendingResource::collection($lent);

        return $lent;
    }
}