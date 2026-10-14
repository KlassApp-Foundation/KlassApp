{{-- SPDX-License-Identifier: MIT --}}
@if($parents === [])
    <p style="margin:0;color:#475569;">No parent linked.</p>
@else
    <ul class="student-guardians" data-testid="student-guardians">
        @foreach($parents as $parent)
            <li class="student-guardian" data-testid="student-guardian">
                <span class="student-guardian__avatar" aria-hidden="true">{{ $parent['initials'] }}</span>
                <span class="student-guardian__body">
                    <span class="student-guardian__name">{{ $parent['name'] }}</span>
                    @if(! empty($parent['relation']))
                        <span class="student-guardian__chip" data-testid="student-guardian-relation">{{ $parent['relation'] }}</span>
                    @endif
                    @if(! empty($parent['phone_href']))
                        <a class="student-guardian__phone" href="{{ $parent['phone_href'] }}">{{ $parent['phone'] }}</a>
                    @endif
                    <span class="student-guardian__chip" data-testid="student-guardian-whatsapp">{{ $parent['whatsapp'] }}</span>
                </span>
            </li>
        @endforeach
    </ul>
@endif
