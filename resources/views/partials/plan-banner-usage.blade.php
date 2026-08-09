{{-- SPDX-License-Identifier: MIT --}}
{{-- Plan usage meters only (active plan). Alert/no-plan copy lives in setup-banner. --}}
@if($plan && !empty($planUsage))
    @php
        $studentPct = $planUsage['students']['limit'] > 0 ? round(($planUsage['students']['used'] / $planUsage['students']['limit']) * 100) : 0;
        $teacherPct = $planUsage['teachers']['limit'] > 0 ? round(($planUsage['teachers']['used'] / $planUsage['teachers']['limit']) * 100) : 0;
        $barColor = fn($pct) => $pct >= 95 ? '#EF4444' : ($pct >= 80 ? '#D97706' : '#22C55E');
        $nearLimit = $studentPct >= 80 || $teacherPct >= 80;
    @endphp
    <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px; font-family: 'DM Sans', sans-serif;" data-testid="plan-usage-banner">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; border-radius: 10px; background: {{ $nearLimit ? '#FEF2F2' : '#F0FDF4' }}; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    {{ $nearLimit ? '⚠️' : '📊' }}
                </div>
                <div>
                    <div style="font-size: 14px; font-weight: 600; color: #0F172A;">
                        {{ ucfirst($plan->name) }} Plan
                    </div>
                    <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
                        @if($plan->amount > 0)
                            ${{ number_format($plan->amount) }} / {{ $plan->cycle }} days
                        @else
                            Free plan
                        @endif
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 24px; flex-wrap: wrap;">
                <div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.03em;">Students</div>
                    <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 4px;">
                        {{ $planUsage['students']['used'] }} / {{ $planUsage['students']['limit'] }}
                    </div>
                    <div style="width: 80px; height: 4px; background: #E2E8F0; border-radius: 2px; margin-top: 4px;">
                        <div style="width: {{ min($studentPct, 100) }}%; height: 100%; background: {{ $barColor($studentPct) }}; border-radius: 2px; transition: width 0.3s;"></div>
                    </div>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.03em;">Teachers</div>
                    <div style="font-size: 13px; font-weight: 600; color: #0F172A; margin-top: 4px;">
                        {{ $planUsage['teachers']['used'] }} / {{ $planUsage['teachers']['limit'] }}
                    </div>
                    <div style="width: 80px; height: 4px; background: #E2E8F0; border-radius: 2px; margin-top: 4px;">
                        <div style="width: {{ min($teacherPct, 100) }}%; height: 100%; background: {{ $barColor($teacherPct) }}; border-radius: 2px; transition: width 0.3s;"></div>
                    </div>
                </div>
            </div>

            @if($nearLimit)
                <a href="{{ url('/admin/subscriptions') }}" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #1E6FD9; color: #FFFFFF; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none;">
                    Upgrade
                </a>
            @endif
        </div>
    </div>
@endif
