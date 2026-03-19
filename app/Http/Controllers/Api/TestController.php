<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\SiteHelper;
use App\Events\PushEvent;
use App\Models\Events;
use App\Models\User;
use Exception;

class TestController extends Controller
{
 	public function index()
 	{
 	 	$user=User::get()->groupBy('school_id');
 	 	return $user;
 	}

 	public function teachers()
 	{
 	 	$user=User::ByRole(5)->get()->groupBy('school_id');
 	 	return $user;
 	}

 	public function parents()
 	{
 	 	$user=User::ByRole(7)->get()->groupBy('school_id');
 	 	return $user;
 	}

 	public function events()
 	{
 	 	$event=Events::get()->groupBy('school_id');
 	 	return $event;
 	}

 	public function getBloodGroup()
 	{
 		$bloodGroup = SiteHelper::getBloodGroups();

        return response()->json([
            'success'   =>  true,
            'message'   =>  'Blood Group List',
            'data'      =>  $bloodGroup
        ],200);
 	}
}