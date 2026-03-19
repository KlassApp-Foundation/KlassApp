{{-- SPDX-License-Identifier: MIT --}}
@foreach($tasks as $tasklist)
<!-- @if($key==1) -->
<p class="text-gray-800 text-sm font-semibold flex items-center">Today Tasks
                        <span class="bg-teal-400 w-4 h-4 flex items-center justify-center text-xs mx-2 rounded-full text-white inline-block">{{$tasklist->TodayTask}}</span>
                         <div class="toggle_container" id="show_hide_{{$loop->iteration}}" onclick="customtoggle('today{{$class_id}}')">
                    <svg class="w-3 h-3 fill-current text-gray-500" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 213.333 213.333" style="enable-background:new 0 0 213.333 213.333;" xml:space="preserve"><g><g><polygon points="0,53.333 106.667,160 213.333,53.333"/></g></g></svg>
                  </div>
                    
                  </p>
      <div class="w-1/4 px-2 my-5">
    @foreach($tasklist as $task)
      <div class="flex items-start my-2 block task_date {{$class}}">
                  <input type="checkbox" name="selected_values[]"  class="my-1" value="{{$task->id}}" onclick="show('{{$task->id}}')">
                   
                  <div class="mx-3">
                  
                    <p class="text-black-800 text-xs font-bold">{{$task->to_do_list}}</p>
                  </div>
                </div>
    @endforeach
    </div>
 <!--  @endif -->

  @endforeach