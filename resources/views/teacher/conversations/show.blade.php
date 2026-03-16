{{-- SPDX-License-Identifier: MIT --}}
@extends('layouts.teacher.layout')

@section('content')
<div class="container">
@include('teacher.conversations.partials._header')

    <div class="row">
        <div class="col-md-4">
          <livewire:conversations.conversation-list :conversations="$conversations" />
        </div>

          <div class="col-md-8">
          	<div class="bg-white">
          		<div class="p-4 border-bottom">
          			 <livewire:conversations.conversation-users :users="$conversation->users" />
          		</div>
          		<div class="p-4" style="height: 300px; max-height: 300px; overflow: scroll;">
          			 <livewire:conversations.conversation-messages  :conversation="$conversation" :messages="$conversation->messages" />
          		</div>
          		<div class="p-2 border-top">
          			 <livewire:conversations.conversation-reply :conversation="$conversation"  />
          		</div>
          	</div>
        </div>
    </div>
</div>
@endsection
