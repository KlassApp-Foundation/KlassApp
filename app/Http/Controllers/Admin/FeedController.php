<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\Classwall\PostRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Traits\Common;
use App\Models\Post;
use App\Models\PostTag;
use App\Models\Tag;
use App\Models\User;
use Exception;

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

        // get Tag data and join with post_tag
        $tags = Tag::join('post_tags', 'tags.id', '=', 'post_tags.tag_id')
        // group by tags.id in order to count number of rows in join and to get each tag only once
        ->groupBy('tags.id')
        // get only columns from tags table along with aggregate COUNT column    
        ->select(['tags.*', \DB::raw('COUNT(*) as cnt')])
        // order by count in descending order
        ->orderBy('cnt', 'desc')
        ->take(20)
        ->get();

        $birthday = $this->getFilePath('uploads/images/birthday.jpg');
        $anniversary = $this->getFilePath('uploads/images/work_anniversary.jpg');
        $exam = $this->getFilePath('uploads/images/exam-banner.jpg');
        $leftarrow = $this->getFilePath('uploads/static/arrow-l.png');
        $rightarrow = $this->getFilePath('uploads/static/arrow-r.png');  


        if($request->list!='')
        {
            $category=$request->list;

            $feeds=Post::where('visibility',$category)->paginate(5);
        }
        elseif($request->search!='')
        {
            $category=$request->search;

            $tags=Tag::where('tag_name',$category)->first();
            
            $post_tag=PostTag::where('tag_id',$tags->id)->pluck('post_id')->toArray();

                     
            $feeds=Post::whereIn('id',$post_tag)->paginate(5);            

        }
        else
        {
            $feeds=Post::orderBy('posted_at','desc')->paginate(5);

        }  

        $entity_id      = Auth::id();
        $entity_name    = 'App\Models\User';
//dd($feeds);
        return view('/admin/feed/feed',['feeds'=>$feeds,'tags'=>$tags,'birthday'=>$birthday, 'anniversary'=>$anniversary,'exam'=>$exam,'leftarrow'=>$leftarrow,'rightarrow'=>$rightarrow,'entity_id' => $entity_id , 'entity_name' => $entity_name,]);
    }

    public function create(Request $request)
    {
        //
        if(count((array)\Request::getQueryString())>0)
        {
            if($request->entity_id != '')
            { 
                $entity_id = $request->entity_id;
            }
            if($request->entity_name != '')
            { 
                $entity_name = $request->entity_name;
            }
        }
        else
        {
            $entity_id      = Auth::id();
            $entity_name    = 'App\Models\User';
        }

        return view('/admin/feed/feed' , [ 'entity_id' => $entity_id , 'entity_name' => $entity_name, ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filter(Request $request)
    {
        $tags = Tag::join('post_tag', 'tags.id', '=', 'post_tag.tag_id')
        // group by tags.id in order to count number of rows in join and to get each tag only once
        ->groupBy('tags.id')
        // get only columns from tags table along with aggregate COUNT column    
        ->select(['tags.*', \DB::raw('COUNT(*) as cnt')])
        // order by count in descending order
        ->orderBy('cnt', 'desc')
        ->take(20)
        ->get();

        $birthday = $this->getFilePath('uploads/images/birthday.jpg');
        $anniversary = $this->getFilePath('uploads/images/work_anniversary.jpg');
        $exam = $this->getFilePath('uploads/images/exam-banner.jpg');
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
            //dd($post_tag);          
            $feeds=Post::whereIn('id',$post_tag)->get();            

        }


        return view('/admin/feed/filter',['feeds'=>$feeds,'tags'=>$tags,'birthday'=>$birthday, 'anniversary'=>$anniversary,'exam'=>$exam]);
    }
}