<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 mt-16 ">
    @foreach($plans as $plan)
        <div
            class="relative bg-white/80 backdrop-blur-sm rounded-3xl border border-green-300 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 overflow-hidden max-w-xs"
        >

            {{-- Popular Badge --}}
            @if($loop->index == 2)
                <div class="absolute top-1 right-2">
                    <span class="bg-gradient-to-r from-green-400 to-blue-500 text-white text-xs px-3 py- rounded-full font-semibold">
                        MOST POPULAR
                    </span>
                </div>
            @endif

            {{-- Header --}}
            <div class="bg-gradient-to-br from-yellow-500 via-red-400 to-pink-500 p-6 text-center">
                <h3 class="text-2xl font-bold text-white">
                    {{ $plan->display_name }}
                </h3>

                <div class="mt-6">
                    @if($plan->amount > 0)
                        <div class="flex justify-center items-center">
                            <span class="text-white text-lg font-semibold">UGX</span>
                            <span class="text-3xl font-extrabold text-white">
                                {{ number_format($plan->amount) }}
                            </span>
                        </div>

                        <p class="text-red-100 mt-2">
                            Every {{ $plan->cycle }} days
                        </p>
                    @else
                        <span class="text-5xl font-extrabold text-white">
                            Free
                        </span>
                        <p class="text-red-100 mt-2">
                            Forever
                        </p>
                    @endif
                </div>
            </div>

            {{-- Features --}}
            <div class="p-8">
                <ul class="space-y-4 text-gray-700">
                    <li class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                  clip-rule="evenodd"/>
                        </svg>
                        <span>{{ $plan->no_of_users }} Users</span>
                    </li>

                    @if($plan->no_of_students)
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                      d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                      clip-rule="evenodd"/>
                            </svg>
                            <span>{{ $plan->no_of_students }} Students</span>
                        </li>
                    @endif

                    <li class="flex items-center gap-3">
                        <span class="text-green-500">✓</span>
                        {{ $plan->no_of_events }} Events
                    </li>

                    <li class="flex items-center gap-3">
                        <span class="text-green-500">✓</span>
                        {{ $plan->no_of_folders }} Folders
                    </li>

                    <li class="flex items-center gap-3">
                        <span class="text-green-500">✓</span>
                        {{ $plan->no_of_files }} Files
                    </li>

                    @if($plan->no_of_videos)
                        <li class="flex items-center gap-3">
                            <span class="text-green-500">✓</span>
                            {{ $plan->no_of_videos }} Videos
                        </li>
                    @endif

                    @if($plan->no_of_audios)
                        <li class="flex items-center gap-3">
                            <span class="text-green-500">✓</span>
                            {{ $plan->no_of_audios }} Audios
                        </li>
                    @endif

                    <li class="flex items-center gap-3">
                        <span class="text-green-500">✓</span>
                        {{ $plan->no_of_bulletins }} Bulletins
                    </li>

                    <li class="flex items-center gap-3">
                        <span class="text-green-500">✓</span>
                        {{ $plan->no_of_groups }} Groups
                    </li>
                </ul>

                {{-- CTA --}}
                <div class="mt-10">
                    <a
                        href="{{ route('register', ["plan" => $plan->name]) }}"
                        class="block w-full text-center bg-gray-900 hover:bg-black text-white font-semibold py-4 rounded-2xl transition-all duration-300 shadow-lg hover:shadow-xl"
                    >
                        {{ $plan->amount == 0 ? 'Get Started Free' : 'Choose Plan' }}
                    </a>
                </div>
            </div>
        </div>
    @endforeach
</div>