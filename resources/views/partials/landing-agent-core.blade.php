{{-- #agent-core visual (tower v2) — KlassApp core + Toshi, 6 channels in, 3 roles out.
     Separate from #toshi's orbital tower (partials/landing-toshi-tower). All classes ac-*.
     Desktop (>=760px): fixed 1120x600 stage scaled to fit by landing-preview.js.
     Below 760px: pure-CSS stacked layout (channels grid → tower crop → roles).
     Source of truth: design system concepts/toshi-tower/v2.html. --}}
<div class="ac-visual reveal">
  <div class="ac-fit" id="agentCoreFit">
    <div class="ac-stage" id="agentCoreStage">

      <div class="ac-tower">
        <div class="ac-tower-inner">
          <svg class="ac-art" viewBox="0 0 1120 600" role="img" aria-labelledby="ac-title ac-desc">
            <title id="ac-title">Toshi on top of KlassApp</title>
            <desc id="ac-desc">WhatsApp, Email, Slack, SMS, Drive and Calendar feed the KlassApp core. Toshi sits above it and acts for parents, teachers and admins.</desc>
            <defs>
              <linearGradient id="ac-gIn" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-green" stop-opacity=".18"/><stop offset=".55" class="ac-stop-green" stop-opacity=".42"/><stop offset="1" class="ac-stop-green" stop-opacity=".72"/></linearGradient>
              <linearGradient id="ac-bIn" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-blue" stop-opacity=".16"/><stop offset=".55" class="ac-stop-blue" stop-opacity=".38"/><stop offset="1" class="ac-stop-blue" stop-opacity=".66"/></linearGradient>
              <linearGradient id="ac-vIn" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-violet" stop-opacity=".16"/><stop offset=".55" class="ac-stop-violet" stop-opacity=".38"/><stop offset="1" class="ac-stop-violet" stop-opacity=".66"/></linearGradient>
              <linearGradient id="ac-aIn" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-amber" stop-opacity=".16"/><stop offset=".55" class="ac-stop-amber" stop-opacity=".38"/><stop offset="1" class="ac-stop-amber" stop-opacity=".66"/></linearGradient>
              <linearGradient id="ac-gOut" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-green" stop-opacity=".70"/><stop offset=".5" class="ac-stop-green" stop-opacity=".42"/><stop offset="1" class="ac-stop-green" stop-opacity=".36"/></linearGradient>
              <linearGradient id="ac-bOut" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-blue" stop-opacity=".66"/><stop offset=".5" class="ac-stop-blue" stop-opacity=".40"/><stop offset="1" class="ac-stop-blue" stop-opacity=".34"/></linearGradient>
              <linearGradient id="ac-aOut" x1="0" y1="0" x2="1" y2="0"><stop offset="0" class="ac-stop-amber" stop-opacity=".66"/><stop offset=".5" class="ac-stop-amber" stop-opacity=".38"/><stop offset="1" class="ac-stop-amber" stop-opacity=".32"/></linearGradient>
              <linearGradient id="ac-coreTop" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#1E293B"/><stop offset="1" stop-color="#0F172A"/></linearGradient>
              <linearGradient id="ac-coreL" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#1E293B"/><stop offset="1" stop-color="#0F172A"/></linearGradient>
              <linearGradient id="ac-tTop" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2DD46A"/><stop offset=".42" stop-color="#22C55E"/><stop offset="1" stop-color="#16A34A"/></linearGradient>
              <linearGradient id="ac-tL" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#22C55E"/><stop offset="1" stop-color="#16A34A"/></linearGradient>
              <radialGradient id="ac-bloomG"><stop offset="0" stop-color="#22C55E" stop-opacity=".28"/><stop offset=".32" stop-color="#22C55E" stop-opacity=".12"/><stop offset=".55" stop-color="#8B5CF6" stop-opacity=".06"/><stop offset=".72" stop-color="#8B5CF6" stop-opacity="0"/></radialGradient>
              <radialGradient id="ac-pool"><stop offset="0" stop-color="#22C55E" stop-opacity=".55"/><stop offset="1" stop-color="#22C55E" stop-opacity="0"/></radialGradient>
              <linearGradient id="ac-beam" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#22C55E" stop-opacity=".7"/><stop offset="1" stop-color="#22C55E" stop-opacity="0"/></linearGradient>
              <filter id="ac-soft" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="8"/></filter>
            </defs>
            <ellipse class="ac-ground" cx="560" cy="548" rx="210" ry="26" fill="#0F172A" opacity=".10" filter="url(#ac-soft)"/>

            {{-- channels → core (left face edge x=410) --}}
            <g class="ac-lines">
              <path class="ac-ln" stroke="url(#ac-gIn)" d="M216 130 C296 130 330 400 410 400"/>
              <path class="ac-ln" stroke="url(#ac-bIn)" d="M230 202 C310 202 330 411 410 411"/>
              <path class="ac-ln" stroke="url(#ac-vIn)" d="M244 274 C324 274 330 422 410 422"/>
              <path class="ac-ln" stroke="url(#ac-gIn)" d="M258 346 C338 346 330 433 410 433"/>
              <path class="ac-ln" stroke="url(#ac-aIn)" d="M272 418 C352 418 330 444 410 444"/>
              <path class="ac-ln" stroke="url(#ac-bIn)" d="M286 490 C366 490 330 455 410 455"/>
            </g>
            <g class="ac-lines" aria-hidden="true">
              <path class="ac-fl" stroke="#22C55E" d="M216 130 C296 130 330 400 410 400"/>
              <path class="ac-fl" stroke="#1E6FD9" d="M230 202 C310 202 330 411 410 411" style="animation-delay:-.3s"/>
              <path class="ac-fl" stroke="#8B5CF6" d="M244 274 C324 274 330 422 410 422" style="animation-delay:-.6s"/>
              <path class="ac-fl" stroke="#22C55E" d="M258 346 C338 346 330 433 410 433" style="animation-delay:-.9s"/>
              <path class="ac-fl" stroke="#D97706" d="M272 418 C352 418 330 444 410 444" style="animation-delay:-1.2s"/>
              <path class="ac-fl" stroke="#1E6FD9" d="M286 490 C366 490 330 455 410 455" style="animation-delay:-1.5s"/>
            </g>

            {{-- KlassApp core block (navy) --}}
            <path d="M410 390 L560 465 L560 535 L410 460 Z" fill="url(#ac-coreL)"/>
            <path d="M560 465 L710 390 L710 460 L560 535 Z" fill="#0F172A"/>
            <path d="M410 390 L560 315 L710 390 L560 465 Z" fill="url(#ac-coreTop)"/>
            <path d="M410 390 L560 315 L710 390 L560 465 Z" fill="none" stroke="#22C55E" stroke-opacity=".45" stroke-width="1"/>
            <path d="M560 465 L560 535" stroke="#22C55E" stroke-opacity=".3" stroke-width="1"/>
            <g class="ac-dots" fill="#22C55E"><circle cx="410" cy="400" r="3"/><circle cx="410" cy="411" r="3"/><circle cx="410" cy="422" r="3"/><circle cx="410" cy="433" r="3"/><circle cx="410" cy="444" r="3"/><circle cx="410" cy="455" r="3"/></g>

            {{-- Toshi's light on the core, and the bloom behind Toshi --}}
            <ellipse class="ac-bloom" cx="560" cy="390" rx="92" ry="46" fill="url(#ac-pool)"/>
            <rect x="554" y="330" width="12" height="60" fill="url(#ac-beam)" opacity=".6"/>
            <circle class="ac-bloom" cx="560" cy="262" r="150" fill="url(#ac-bloomG)"/>

            {{-- Toshi → roles (right face edge x=660) --}}
            <g class="ac-lines">
              <path class="ac-ln" stroke="url(#ac-gOut)" d="M660 262 C760 262 800 190 880 190"/>
              <path class="ac-ln" stroke="url(#ac-bOut)" d="M660 278 C760 278 800 280 880 281"/>
              <path class="ac-ln" stroke="url(#ac-aOut)" d="M660 294 C760 294 800 370 880 370"/>
            </g>
            <g class="ac-lines" aria-hidden="true">
              <path class="ac-fl" stroke="#22C55E" d="M660 262 C760 262 800 190 880 190"/>
              <path class="ac-fl" stroke="#1E6FD9" d="M660 278 C760 278 800 280 880 281" style="animation-delay:-.5s"/>
              <path class="ac-fl" stroke="#D97706" d="M660 294 C760 294 800 370 880 370" style="animation-delay:-1s"/>
            </g>

            {{-- Toshi block (green) --}}
            <g class="ac-breath">
              <path d="M460 250 L560 300 L560 356 L460 306 Z" fill="url(#ac-tL)"/>
              <path d="M560 300 L660 250 L660 306 L560 356 Z" fill="#16A34A"/>
              <path d="M460 250 L560 200 L660 250 L560 300 Z" fill="url(#ac-tTop)"/>
              <path d="M460 250 L560 200 L660 250" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="1"/>
              <ellipse cx="560" cy="248" rx="30" ry="15" fill="#0F172A" opacity=".18"/>
            </g>
            <g class="ac-dots" fill="#22C55E"><circle cx="660" cy="262" r="3"/><circle cx="660" cy="278" r="3"/><circle cx="660" cy="294" r="3"/></g>
          </svg>

          {{-- Plate and K are siblings: only the plate spins. The K must stay unanimated
               (a reverse spin on a sibling makes the mark itself rotate). --}}
          <div class="ac-ktile"><div class="ac-plate"></div><img src="{{ asset('images/klassapp-icon.svg') }}" alt="KlassApp" width="44" height="44"></div>

          {{-- One model mark at a time, alternating faces. Decorative: the "Runs on" row names all six. --}}
          <div class="ac-mts" id="agentCoreModels">
            <div class="ac-mt ac-mt-l"><img src="{{ asset('images/brand/models/anthropic-mark.svg') }}" alt="" width="22" height="22"></div>
            <div class="ac-mt ac-mt-r"><img src="{{ asset('images/brand/models/openai-mark.svg') }}" alt="" width="22" height="22"></div>
            <div class="ac-mt ac-mt-l"><img src="{{ asset('images/brand/models/google-gemini-mark.svg') }}" alt="" width="22" height="22"></div>
            <div class="ac-mt ac-mt-r"><img src="{{ asset('images/brand/models/xai-grok-mark.svg') }}" alt="" width="22" height="22"></div>
            <div class="ac-mt ac-mt-l"><img src="{{ asset('images/brand/models/moonshot-kimi-mark.svg') }}" alt="" width="22" height="22"></div>
            <div class="ac-mt ac-mt-r"><img src="{{ asset('images/brand/models/zhipu-zai-mark.svg') }}" alt="" width="22" height="22"></div>
          </div>
        </div>
      </div>

      <div class="toshi-visual-label ac-lbl">Toshi</div>
      <div class="ac-sub">KlassApp · Core</div>
      <span class="ac-beam ac-beam-top" aria-hidden="true"></span>
      <span class="ac-beam ac-beam-bottom" aria-hidden="true"></span>

      <div class="ac-channels">
        <div class="ac-ch ac-node n-green"><span class="ico brand-well"><x-brand.whatsapp /></span><span>WhatsApp</span></div>
        <div class="ac-ch ac-node"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 7L2 7"/></svg></span><span>Email</span></div>
        <div class="ac-ch ac-node n-violet"><span class="ico brand-well"><x-brand.slack /></span><span>Slack</span></div>
        <div class="ac-ch ac-node n-green"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span><span>SMS</span></div>
        <div class="ac-ch ac-node n-amber"><span class="ico brand-well"><x-brand.google-drive /></span><span>Drive</span></div>
        <div class="ac-ch ac-node"><span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span>Calendar</span></div>
      </div>

      <div class="ac-roles">
        <div class="ac-role ac-node n-green"><span class="ac-role-dot"></span>Parent</div>
        <div class="ac-role ac-node"><span class="ac-role-dot"></span>Teacher</div>
        <div class="ac-role ac-node n-amber"><span class="ac-role-dot"></span>Admin</div>
      </div>
    </div>
  </div>

  <div class="ac-models" aria-label="Models Toshi runs on"><b>Runs on</b>
    <span><img src="{{ asset('images/brand/models/anthropic-mark.svg') }}" alt="" width="16" height="16">Anthropic</span>
    <span><img src="{{ asset('images/brand/models/openai-mark.svg') }}" alt="" width="16" height="16">OpenAI</span>
    <span><img src="{{ asset('images/brand/models/google-gemini-mark.svg') }}" alt="" width="16" height="16">Gemini</span>
    <span><img src="{{ asset('images/brand/models/xai-grok-mark.svg') }}" alt="" width="16" height="16">Grok</span>
    <span><img src="{{ asset('images/brand/models/moonshot-kimi-mark.svg') }}" alt="" width="16" height="16">Kimi</span>
    <span><img src="{{ asset('images/brand/models/zhipu-zai-mark.svg') }}" alt="" width="16" height="16">GLM</span>
  </div>
</div>
