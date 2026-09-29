{{-- Toshi agent core — orbiting tools & models (WS-2 / D1+D2). Pure inline SVG + CSS; no deps, no WebGL. --}}
<div class="toshi-tower reveal" id="toshiTower">
  <div class="toshi-tower-stage">
<div class="toshi-depth" aria-hidden="true"></div>
<svg class="tt-svg" viewBox="120 150 760 420" aria-hidden="true" focusable="false">
<defs>
<radialGradient id="tt-glow"><stop offset="0" class="tt-stop-g" stop-opacity=".42"/><stop offset=".55" class="tt-stop-g" stop-opacity=".16"/><stop offset="1" class="tt-stop-g" stop-opacity="0"/></radialGradient>
<radialGradient id="tt-emis"><stop offset="0" stop-color="#FFFFFF"/><stop offset=".62" stop-color="#FFFFFF" stop-opacity=".92"/><stop offset="1" class="tt-stop-g" stop-opacity=".35"/></radialGradient>
<radialGradient id="tt-ao"><stop offset="0" class="tt-stop-d" stop-opacity=".38"/><stop offset="1" class="tt-stop-d" stop-opacity="0"/></radialGradient>
<radialGradient id="tt-floor"><stop offset="0" class="tt-stop-d" stop-opacity=".16"/><stop offset="1" class="tt-stop-d" stop-opacity="0"/></radialGradient>
<linearGradient id="tt-ring-outer" x1="0" y1="0" x2="1" y2="1"><stop offset="0" class="tt-stop-g" stop-opacity="0"/><stop offset=".45" class="tt-stop-g" stop-opacity=".5"/><stop offset="1" class="tt-stop-b" stop-opacity="0"/></linearGradient>
<linearGradient id="tt-ring-inner" x1="0" y1="1" x2="1" y2="0"><stop offset="0" class="tt-stop-g" stop-opacity="0"/><stop offset=".5" class="tt-stop-g" stop-opacity=".45"/><stop offset="1" class="tt-stop-g" stop-opacity="0"/></linearGradient>
<filter id="tt-soft" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="9"/></filter>
<filter id="tt-shadow" x="-60%" y="-60%" width="220%" height="220%"><feDropShadow dx="0" dy="3" stdDeviation="4.5" flood-color="#0F172A" flood-opacity=".18"/></filter>
<clipPath id="tt-globe-clip"><circle cx="500" cy="330" r="63"/></clipPath>
<radialGradient id="tt-pulse"><stop offset=".62" class="tt-stop-g" stop-opacity="0"/><stop offset=".8" class="tt-stop-g" stop-opacity=".7"/><stop offset="1" class="tt-stop-g" stop-opacity="0"/></radialGradient>
</defs>
<ellipse cx="500" cy="520" rx="280" ry="46" fill="url(#tt-floor)"/>
<ellipse cx="500" cy="452" rx="150" ry="32" fill="url(#tt-ao)" filter="url(#tt-soft)"/>
<g class="tt-glow"><ellipse cx="500" cy="330" rx="168" ry="168" fill="url(#tt-glow)"/></g>
{{-- back halves: drawn under the core --}}
<g class="tt-rings">
<path d="M156 330 A344 132 0 0 1 844 330" fill="none" stroke="url(#tt-ring-outer)" stroke-width="3"/>
<path d="M288 330 A212 84 0 0 1 712 330" fill="none" stroke="url(#tt-ring-inner)" stroke-width="2.5"/>
<ellipse class="tt-spin tt-spin-a" cx="500" cy="330" rx="344" ry="132" fill="none" stroke="rgba(34,197,94,.55)" stroke-width="3" stroke-linecap="round" stroke-dasharray="80 1580"/>
<ellipse class="tt-spin tt-spin-b" cx="500" cy="330" rx="212" ry="84" fill="none" stroke="rgba(34,197,94,.7)" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="50 820"/>
</g>
<g class="tt-orbit"><circle cx="823.3" cy="375.1" r="3.2" fill="rgba(34,197,94,.55)"/><circle cx="440.3" cy="460" r="2.8" fill="rgba(30,111,217,.45)"/><circle cx="156" cy="330" r="3.4" fill="rgba(34,197,94,.75)"/><circle cx="440.3" cy="200" r="3" fill="rgba(34,197,94,.55)"/><circle cx="606" cy="402.7" r="2.8" fill="rgba(34,197,94,.75)"/><circle cx="394" cy="257.3" r="2.6" fill="rgba(30,111,217,.45)"/></g>
<g class="tt-core">
<circle class="tt-core-pulse" cx="500" cy="330" r="80" fill="url(#tt-pulse)" opacity=".35"/>
<circle cx="500" cy="330" r="64" fill="url(#tt-emis)"/>
<g class="tt-globe" clip-path="url(#tt-globe-clip)" fill="none" style="stroke:var(--brand-green)" aria-hidden="true">
<g stroke-opacity=".28" stroke-width="1"><ellipse cx="500" cy="298" rx="55" ry="7"/><ellipse cx="500" cy="362" rx="55" ry="7"/></g>
<ellipse cx="500" cy="330" rx="64" ry="10" stroke-opacity=".42" stroke-width="1.2"/>
<g class="tt-k" style="--kd:0s"><image href="{{ asset('images/klassapp-icon.svg') }}" x="484" y="314" width="32" height="32"/></g><g class="tt-k" style="--kd:-16s"><image href="{{ asset('images/klassapp-icon.svg') }}" x="484" y="314" width="32" height="32"/></g>
<g class="tt-meridians" stroke-opacity=".34" stroke-width="1"><circle class="tt-mer" cx="500" cy="330" r="63" vector-effect="non-scaling-stroke" style="--m:0;--ms:1"/><circle class="tt-mer" cx="500" cy="330" r="63" vector-effect="non-scaling-stroke" style="--m:1;--ms:.71"/><circle class="tt-mer" cx="500" cy="330" r="63" vector-effect="non-scaling-stroke" style="--m:2;--ms:0"/><circle class="tt-mer" cx="500" cy="330" r="63" vector-effect="non-scaling-stroke" style="--m:3;--ms:-.71"/></g>
</g>
<circle cx="500" cy="330" r="64" fill="none" class="tt-gs" stroke-opacity=".75" stroke-width="3"/>
</g>
{{-- front halves: drawn over the core --}}
<g class="tt-rings-front">
<path d="M156 330 A344 132 0 0 0 844 330" fill="none" stroke="url(#tt-ring-outer)" stroke-width="3"/>
<path d="M288 330 A212 84 0 0 0 712 330" fill="none" stroke="url(#tt-ring-inner)" stroke-width="2.5"/>
</g>
<g class="tt-nodes">
<g transform="translate(712 330)" class="tt-node"><circle class="tt-beat" r="30" style="--d:0s;--dm:0s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-20 -20) scale(0.228291)"><path fill="#25D366" d="M87.184 25.227c-33.733 0-61.166 27.423-61.178 61.13a60.98 60.98 0 0 0 9.349 32.535l1.455 2.313-6.179 22.558 23.146-6.069 2.235 1.324c9.387 5.571 20.15 8.517 31.126 8.523h.023c33.707 0 61.14-27.426 61.153-61.135a60.75 60.75 0 0 0-17.895-43.251 60.75 60.75 0 0 0-43.235-17.928z"/><path fill="#fff" fill-rule="evenodd" d="M68.772 55.603c-1.378-3.061-2.828-3.123-4.137-3.176l-3.524-.043c-1.226 0-3.218.46-4.902 2.3s-6.435 6.287-6.435 15.332 6.588 17.785 7.506 19.013 12.718 20.381 31.405 27.75c15.529 6.124 18.689 4.906 22.061 4.6s10.877-4.447 12.408-8.74 1.532-7.971 1.073-8.74-1.685-1.226-3.525-2.146-10.877-5.367-12.562-5.981-2.91-.919-4.137.921-4.746 5.979-5.819 7.206-2.144 1.381-3.984.462-7.76-2.861-14.784-9.124c-5.465-4.873-9.154-10.891-10.228-12.73s-.114-2.835.808-3.751c.825-.824 1.838-2.147 2.759-3.22s1.224-1.84 1.836-3.065.307-2.301-.153-3.22-4.032-10.011-5.666-13.647"/></g></g></g>
<g transform="translate(606 402.7)" class="tt-node"><circle class="tt-beat" r="30" style="--d:3s;--dm:3s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-20 -20) scale(0.458191)"><path fill="#0066da" d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z"/><path fill="#00ac47" d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z"/><path fill="#ea4335" d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z"/><path fill="#00832d" d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z"/><path fill="#2684fc" d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z"/><path fill="#ffba00" d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z"/></g></g></g>
<g transform="translate(394 402.7)" class="tt-node"><circle class="tt-beat" r="30" style="--d:6s;--dm:9s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-20 -20) scale(0.314964)"><path fill="#E01E5A" d="M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z"/><path fill="#36C5F0" d="M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z"/><path fill="#2EB67D" d="M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z"/><path fill="#ECB22E" d="M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z"/></g></g></g>
<g transform="translate(288 330)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:9s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-12 -12)"><rect x="2" y="4" width="20" height="16" rx="2.5" fill="none" class="tt-bs" stroke-width="2"/><path d="M3 6l9 7 9-7" fill="none" class="tt-bs" stroke-width="2"/></g></g></g>
<g transform="translate(394 257.3)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:12s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-12 -12)"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" fill="none" class="tt-gs" stroke-width="2"/></g></g></g>
<g transform="translate(606 257.3)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:15s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><g transform="translate(-12 -12)"><rect x="3" y="4" width="18" height="17" rx="2.5" fill="none" class="tt-bs" stroke-width="2"/><path d="M3 9h18M8 2v4M16 2v4" fill="none" class="tt-bs" stroke-width="2"/></g></g></g>
<g transform="translate(797.9 396)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:1.5s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/anthropic-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
<g transform="translate(500 462)" class="tt-node"><circle class="tt-beat" r="30" style="--d:4.5s;--dm:6s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/openai-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
<g transform="translate(202.1 396)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:7.5s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/xai-grok-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
<g transform="translate(202.1 264)" class="tt-node"><circle class="tt-beat" r="30" style="--d:10.5s;--dm:12s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/google-gemini-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
<g transform="translate(500 198)" class="tt-node tt-x"><circle class="tt-beat" r="30" style="--d:13.5s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/moonshot-kimi-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
<g transform="translate(797.9 264)" class="tt-node"><circle class="tt-beat" r="30" style="--d:16.5s;--dm:15s"/><g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" stroke-width="1"/><image href="{{ asset('images/brand/models/zhipu-zai-mark.svg') }}" x="-11" y="-11" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g></g>
</g>
</svg>
  </div>
</div>
