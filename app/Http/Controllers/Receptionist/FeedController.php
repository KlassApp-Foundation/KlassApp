<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Receptionist;

use App\Http\Requests\Classwall\PostRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Models\PostTag;
use App\Traits\Common;
use App\Models\Post;
use App\Models\Tag;

class FeedController extends Controller
{
    //
    use LogActivity;
    use Common;

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {       
        $feeds=Post::where('visibility','all_class')->orderBy('posted_at','DESC')->get();
        
        if($request->list!='')
        {
            $category=$request->list;

            $feeds=Post::where('visibility',$category)->get();
        }
        elseif($request->search!='')
        {
            $category=$request->search;

            $tags=Tag::where('tag_name',$category)->first();
            
            $post_tag=PostTag::where('tag_id',$tags->id)->pluck('post_id')->toArray();
                  
            $feeds=Post::whereIn('id',$post_tag)->get();            
        }
        return view('/reception/feed/feed',['feeds'=>$feeds]);
    }
}