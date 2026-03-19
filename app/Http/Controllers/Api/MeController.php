<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api;

use App\Http\Resources\API\MyInfo as MyInfoResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Exception;

class MeController extends Controller
{
    //
    public function myInfo()
    {
    	$user = User::where('id',Auth::user()->id)->first();

    	$myInfo = new MyInfoResource($user);

    	return response()->json([
    		'success'	=>	true,
    		'message'	=>	'My Details',
            'data'		=>	$myInfo
        ],200);
    }
}
