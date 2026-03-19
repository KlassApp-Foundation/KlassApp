<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Api\Teacher;

use App\Http\Resources\API\Teacher\ShowEvent as ShowEventResource;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Events;

class EventGalleryController extends Controller
{
	public function showimage($event_id)
    {  
    	$event = Events::where([['id',$event_id],['school_id',Auth::user()->school_id]])->get();

    	$event = ShowEventResource::collection($event);

    	return $event;
    }
}