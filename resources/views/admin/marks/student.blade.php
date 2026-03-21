@extends('layouts.admin.layout')
@section('content')

<div class="container-fluid w-full lg:mx-2">
    <div class="flex">
        <div class="">
            <span>School logo</span>
        </div>
        <h3>{{ $learner->school->name }}</h3>
        <div class="">
            <span>Image here</span>
        </div>
    </div>
   
   <div class="">

    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr class="px-6 py-3 text-left text-lg font-medium text-gray-500 uppercase tracking-wider">
                @foreach ($controls as $control)
                    <th>{{ $control }}</th>
                @endforeach
            </tr>
        </thead>

        {{-- ========== body =========== --}}
        <tbody class="divide-y">
            @foreach ($subjects as $subject)
            <tr class="hover:bg-gray-50 transition">
               <td class="px-6 py-4 whitespace-nowrap">{{ str($subject->name ?? 'Subject')->limit(10) }}</td>  
            </tr>
             @endforeach
        </tbody>
    </table>


   </div>
</div>

@endsection
