{{-- SPDX-License-Identifier: MIT --}}
 <div>
    <ul class="list-reset flex text-xs profile-tab flex-wrap">
       <!--  <li class="px-2 mx-3 py-2 {{Request::Segment ('2')=='files'?'active':''}}"><a href="{{url('/admin/quotes')}}" class="text-gray-700 font-medium">Image Upload</a></li>  -->
        <li class="px-2 mx-3 py-2 {{Request::Segment ('2')=='videos'?'active':''}}"><a href="{{url('/admin/textquotes')}}" class="text-gray-700 font-medium">Text Upload</a></li>
    </ul>
</div>
