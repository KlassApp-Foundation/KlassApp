{{-- SPDX-License-Identifier: MIT --}}
@if($parents === [])
    <p style="margin:0;color:#475569;">No parent linked.</p>
@else
    <ul class="student-guardians" data-testid="student-guardians">
        @foreach($parents as $parent)
            <li class="student-guardian" data-testid="student-guardian">
                <x-profile-photo :user="$parent['user']" size="guardian" class="student-guardian__avatar" />
                <span class="student-guardian__body">
                    <span class="student-guardian__name">{{ $parent['name'] }}</span>
                    @if(! empty($parent['relation']))
                        <span class="student-guardian__chip" data-testid="student-guardian-relation">{{ $parent['relation'] }}</span>
                    @endif
                    @if(! empty($parent['phone_href']))
                        <a class="student-guardian__phone" href="{{ $parent['phone_href'] }}">{{ $parent['phone'] }}</a>
                    @endif
                    <span class="student-guardian__chip" data-testid="student-guardian-whatsapp">{{ $parent['whatsapp'] }}</span>
                    <a data-testid="guardian-message" href="{{ $parent['profile_url'] }}" style="min-height:44px;display:inline-flex;align-items:center;padding:0 12px;border:1px solid #E2E8F0;border-radius:8px;background:transparent;color:#0F172A;text-decoration:none;">Message</a>
                </span>
            </li>
        @endforeach
    </ul>
@endif
