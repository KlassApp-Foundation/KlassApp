{{-- SPDX-License-Identifier: MIT --}}
<div class="message-wrapper">
    <div class="message-container space-y-2">
        @if (session('successmessage'))
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative w-full md:w-1/2" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ session('successmessage') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
                {{ session()->forget('successmessage') }}
            </div>
        @endif

        @if (session('failmessage'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative w-full md:w-1/2" role="alert">
                <strong class="font-bold">Error! </strong>
                <span class="block sm:inline">{{ session('failmessage') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
                {{ session()->forget('failmessage') }}
            </div>
        @endif

        {{--
            Validation + thrown-message errors (e.g. MarksLockedException:
            "Marks are locked and cannot be edited... Contact the admin if you
            need them reopened."). Previously silently dropped, so a blocked
            correction looked like it had simply not saved.
        --}}
        @if (isset($errors) && $errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative w-full md:w-1/2" role="alert">
                <strong class="font-bold">Error! </strong>
                @foreach ($errors->all() as $error)
                    <span class="block sm:inline">{{ $error }}</span>
                @endforeach
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
            </div>
        @endif
    </div>
</div>
