{{-- SPDX-License-Identifier: MIT --}}
<div class="message-wrapper">
    <div class="message-container space-y-2">
        @if (session('successmessage'))
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded relative w-1/2" role="alert">
                <strong class="font-bold">Success! </strong>
                <span class="block sm:inline">{{ session('successmessage') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
                {{ session()->forget('successmessage') }}
            </div>
        @endif

        @if (session('failmessage'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative w-1/2" role="alert">
                <strong class="font-bold">Error! </strong>
                <span class="block sm:inline">{{ session('failmessage') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3 cursor-pointer" onclick="this.parentElement.style.display='none';">&times;</span>
                {{ session()->forget('failmessage') }}
            </div>
        @endif
    </div>
</div>