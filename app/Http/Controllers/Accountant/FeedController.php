<?php
/**
 * SPDX-License-Identifier: MIT
 * (c) 2025 GegoSoft Technologies and GegoK12 Contributors
 */
namespace App\Http\Controllers\Accountant;

use App\Http\Requests\Classwall\PostRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentAcademic;
use Illuminate\Http\Request;
use App\Traits\LogActivity;
use App\Helpers\SiteHelper;
use App\Models\PostTag;
use App\Traits\Common;
use App\Models\Post;
use App\Models\Tag;
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
        $feeds=Post::where('visibility','all_class')->orderBy('posted_at',DESC)->get();
        
        $birthday = $this->getFilePath('uploads/images/birthday.jpg');
        $anniversary = $this->getFilePath('uploads/images/work_anniversary.jpg');
        $exam = $this->getFilePath('uploads/images/exam-banner.jpg');

        return view('/accountant/feed/feed',['feeds'=>$feeds,'tags'=>$tags,'birthday'=>$birthday, 'anniversary'=>$anniversary,'exam'=>$exam]);
    }

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
            
            $feeds=Post::whereIn('id',$post_tag)->get();            

        }

        return view('/accountant/feed/filter',['feeds' => $feeds , 'tags' => $tags , 'birthday' => $birthday , 'anniversary' => $anniversary , 'exam' => $exam]);
    }
}