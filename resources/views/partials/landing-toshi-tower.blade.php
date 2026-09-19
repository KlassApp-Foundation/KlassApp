{{-- Toshi agent core — orbiting tools & models (WS-2 / D1+D2). Pure inline SVG + CSS; no deps, no WebGL. --}}
<div class="toshi-tower reveal" id="toshiTower" aria-label="Toshi orchestration visual">
  <div class="toshi-tower-stage">
<svg class="tt-svg" viewBox="120 150 760 420" role="img" aria-labelledby="tt-title tt-desc">
<title id="tt-title">KlassApp Toshi — an agent core orchestrating school tools and AI models</title>
<desc id="tt-desc">A glowing metallic Toshi core sits at the centre, wrapped by two glass orbit rings. School tools (WhatsApp, Google Drive, Slack, email, SMS, calendar) ride the inner ring and AI models (Anthropic, OpenAI, Grok, Gemini, Kimi, Z.ai) the outer ring as upright tiles; the rings and light packets rotate continuously and one tile lights up at a time. Decorative.</desc>
<defs>
<radialGradient id="tt-glow" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#22C55E" stop-opacity=".42"/><stop offset=".55" stop-color="#22C55E" stop-opacity=".16"/><stop offset="1" stop-color="#22C55E" stop-opacity="0"/></radialGradient>
<radialGradient id="tt-metal" cx="36%" cy="30%" r="72%"><stop offset="0" stop-color="#FFFFFF"/><stop offset=".32" stop-color="#E2E8F0"/><stop offset=".66" stop-color="#94A3B8"/><stop offset="1" stop-color="#475569"/></radialGradient>
<radialGradient id="tt-emis" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#ECFDF5"/><stop offset=".45" stop-color="#4ADE80"/><stop offset="1" stop-color="#22C55E" stop-opacity="0"/></radialGradient>
<radialGradient id="tt-ao" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#0F172A" stop-opacity=".38"/><stop offset="1" stop-color="#0F172A" stop-opacity="0"/></radialGradient>
<radialGradient id="tt-floor" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#0F172A" stop-opacity=".16"/><stop offset="1" stop-color="#0F172A" stop-opacity="0"/></radialGradient>
<linearGradient id="tt-ring-outer" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#22C55E" stop-opacity="0"/><stop offset=".45" stop-color="#22C55E" stop-opacity=".5"/><stop offset="1" stop-color="#1E6FD9" stop-opacity="0"/></linearGradient>
<linearGradient id="tt-ring-inner" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#34D399" stop-opacity="0"/><stop offset=".5" stop-color="#4ADE80" stop-opacity=".45"/><stop offset="1" stop-color="#22C55E" stop-opacity="0"/></linearGradient>
<filter id="tt-soft" x="-40%" y="-40%" width="180%" height="180%"><feGaussianBlur stdDeviation="9"/></filter>
<filter id="tt-shadow" x="-60%" y="-60%" width="220%" height="220%"><feDropShadow dx="0" dy="3" stdDeviation="4.5" flood-color="#0F172A" flood-opacity=".18"/></filter>
<filter id="tt-beatblur" x="-60%" y="-60%" width="220%" height="220%"><feGaussianBlur stdDeviation="4"/></filter>
</defs>
<ellipse cx="500" cy="520" rx="280" ry="46" fill="url(#tt-floor)"/>
<ellipse cx="500" cy="452" rx="150" ry="32" fill="url(#tt-ao)" filter="url(#tt-soft)"/>
<g class="tt-rings">
  <ellipse cx="500" cy="330" rx="344" ry="132" fill="none" stroke="url(#tt-ring-outer)" stroke-width="3"/>
  <ellipse cx="500" cy="330" rx="212" ry="84" fill="none" stroke="url(#tt-ring-inner)" stroke-width="2.5"/>
  <ellipse class="tt-spin tt-spin-a" cx="500" cy="330" rx="344" ry="132" fill="none" stroke="#86EFAC" stroke-width="3" stroke-linecap="round" stroke-dasharray="80 1580"/>
  <ellipse class="tt-spin tt-spin-b" cx="500" cy="330" rx="212" ry="84" fill="none" stroke="#34D399" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="50 820"/>
</g>
<g class="tt-orbit"><circle cx="823.3" cy="375.1" r="3.2" fill="#86EFAC"/>
<circle cx="440.3" cy="460.0" r="2.8" fill="#93C5FD"/>
<circle cx="156.0" cy="330.0" r="3.4" fill="#4ADE80"/>
<circle cx="440.3" cy="200.0" r="3.0" fill="#86EFAC"/>
<circle cx="606.0" cy="402.7" r="2.8" fill="#4ADE80"/>
<circle cx="394.0" cy="257.3" r="2.6" fill="#93C5FD"/></g>
<g class="tt-core">
  <ellipse cx="500" cy="330" rx="168" ry="168" fill="url(#tt-glow)"/>
  <circle class="tt-core-pulse" cx="500" cy="330" r="64" fill="none" stroke="#86EFAC" stroke-width="10" opacity=".35" filter="url(#tt-beatblur)"/>
  <circle cx="500" cy="330" r="86" fill="url(#tt-metal)"/>
  <circle cx="500" cy="330" r="86" fill="none" stroke="#FFFFFF" stroke-opacity=".35" stroke-width="1.5"/>
  <circle cx="500" cy="330" r="64" fill="url(#tt-emis)"/>
  <circle cx="500" cy="330" r="64" fill="none" stroke="#22C55E" stroke-opacity=".75" stroke-width="3"/>
  <ellipse cx="500" cy="330" rx="86" ry="30" fill="none" stroke="#FFFFFF" stroke-opacity=".22" stroke-width="1.5"/>
  <ellipse cx="500" cy="300" rx="66" ry="22" fill="none" stroke="#FFFFFF" stroke-opacity=".16" stroke-width="1.2"/>
  <ellipse cx="474" cy="290" rx="30" ry="15" fill="#FFFFFF" opacity=".85" transform="rotate(-28 474 290)"/>
  <g class="tt-core-mark" transform="translate(500 330)" filter="url(#tt-shadow)">
    <rect x="-27" y="-27" width="54" height="54" rx="15" fill="#FFFFFF" stroke="#e8e6dc"/>
    <g transform="translate(-11 -11) scale(0.011)" aria-hidden="true">
      <path fill="#29BF5D" d="M 378.864 350.858 C 385.088 348.768 450.325 349.948 461.644 349.979 L 622.362 350.001 L 700.987 349.972 C 709.322 349.987 725.372 349.089 732.865 350.078 C 736.897 356.014 735.183 451.793 735.073 466.536 L 735.147 584.984 L 735.089 688.086 C 735.175 703.102 736.427 736.248 734.864 750.155 C 723.477 763.143 709.116 777.04 696.554 788.99 C 650.548 832.75 606.335 880.404 559.2 922.84 C 553.485 929.758 540.665 941.688 533.636 948.596 L 478.624 1002.24 L 416.254 1064.18 C 408.25 1072.17 386.579 1094.81 378.945 1099.47 C 379.279 1084.83 378.601 1068.03 378.593 1053 L 378.565 934.469 L 378.317 555.985 L 378.376 420.358 C 378.365 403.566 377.247 365.807 378.864 350.858 z"/>
      <path fill="#0273D4" d="M 378.864 350.858 C 384.578 355.299 394.789 366.74 400.483 372.668 C 411.189 383.686 421.978 394.623 432.849 405.478 L 510.577 483.974 C 525.592 499.16 542.304 515.403 556.629 531.076 C 557.775 564.211 556.95 601.948 556.935 635.532 L 556.991 826.478 C 557.016 841.809 555.929 912.754 559.2 922.84 C 553.485 929.758 540.665 941.688 533.636 948.596 L 478.624 1002.24 L 416.254 1064.18 C 408.25 1072.17 386.579 1094.81 378.945 1099.47 C 379.279 1084.83 378.601 1068.03 378.593 1053 L 378.565 934.469 L 378.317 555.985 L 378.376 420.358 C 378.365 403.566 377.247 365.807 378.864 350.858 z"/>
      <path fill="#29BF5D" d="M 937.312 308.43 L 1252.36 347.197 C 1272.83 349.59 1405.89 362.449 1414.84 368.145 C 1424.19 377.072 1434.1 389.5 1442.59 399.548 C 1454.91 414.191 1467.34 428.742 1479.89 443.199 L 1630.48 619.466 L 1675.74 672.676 C 1687.47 686.702 1705.13 708.327 1717.88 720.982 C 1695.59 716.864 1673.25 712.953 1650.88 709.25 C 1638.09 707.257 1616.76 705.097 1605.36 701.885 C 1604.61 732.845 1604.34 763.815 1604.55 794.784 C 1604.6 807.173 1604.11 829.33 1605.21 841.082 C 1617.05 850.147 1625.98 857.245 1628.03 872.979 C 1631.53 900.001 1614.71 901.771 1612.94 916.456 C 1612.05 923.921 1632.93 1031.15 1636.64 1043.31 C 1614.22 1042.77 1565.97 1041.94 1544.62 1043.67 C 1545.82 1026.54 1554.47 990.553 1557.82 972.228 C 1559.98 960.333 1566.7 924.142 1570 913.639 C 1542.73 889.862 1543.37 861.146 1575.13 841.893 C 1574.3 796.018 1573.54 742.854 1574.9 696.95 C 1539.15 690.118 1504.3 684.666 1468.63 678.477 L 1325.57 653.111 C 1315.35 651.32 1256.34 642.965 1251.54 639.709 C 1242.38 634.536 1215.19 603.544 1206.28 594.124 C 1176.73 563.34 1147.47 532.268 1118.52 500.915 C 1098.9 479.888 1078.86 459.663 1059.12 438.904 L 937.312 308.43 z"/>
    </g>
  </g>
</g>
<g class="tt-nodes">
<g transform="translate(712.0 330.0)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:0.0s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-20 -20) scale(0.228291)"><path fill="#25D366" d="M87.184 25.227c-33.733 0-61.166 27.423-61.178 61.13a60.98 60.98 0 0 0 9.349 32.535l1.455 2.313-6.179 22.558 23.146-6.069 2.235 1.324c9.387 5.571 20.15 8.517 31.126 8.523h.023c33.707 0 61.14-27.426 61.153-61.135a60.75 60.75 0 0 0-17.895-43.251 60.75 60.75 0 0 0-43.235-17.928z"/><path fill="#fff" fill-rule="evenodd" d="M68.772 55.603c-1.378-3.061-2.828-3.123-4.137-3.176l-3.524-.043c-1.226 0-3.218.46-4.902 2.3s-6.435 6.287-6.435 15.332 6.588 17.785 7.506 19.013 12.718 20.381 31.405 27.75c15.529 6.124 18.689 4.906 22.061 4.6s10.877-4.447 12.408-8.74 1.532-7.971 1.073-8.74-1.685-1.226-3.525-2.146-10.877-5.367-12.562-5.981-2.91-.919-4.137.921-4.746 5.979-5.819 7.206-2.144 1.381-3.984.462-7.76-2.861-14.784-9.124c-5.465-4.873-9.154-10.891-10.228-12.73s-.114-2.835.808-3.751c.825-.824 1.838-2.147 2.759-3.22s1.224-1.84 1.836-3.065.307-2.301-.153-3.22-4.032-10.011-5.666-13.647"/></g></g>
</g>
<g transform="translate(606.0 402.7)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:2.6s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-20 -20) scale(0.458191)"><path fill="#0066da" d="m6.6 66.85 3.85 6.65c.8 1.4 1.95 2.5 3.3 3.3l13.75-23.8h-27.5c0 1.55.4 3.1 1.2 4.5z"/><path fill="#00ac47" d="m43.65 25-13.75-23.8c-1.35.8-2.5 1.9-3.3 3.3l-25.4 44a9.06 9.06 0 0 0 -1.2 4.5h27.5z"/><path fill="#ea4335" d="m73.55 76.8c1.35-.8 2.5-1.9 3.3-3.3l1.6-2.75 7.65-13.25c.8-1.4 1.2-2.95 1.2-4.5h-27.502l5.852 11.5z"/><path fill="#00832d" d="m43.65 25 13.75-23.8c-1.35-.8-2.9-1.2-4.5-1.2h-18.5c-1.6 0-3.15.45-4.5 1.2z"/><path fill="#2684fc" d="m59.8 53h-32.3l-13.75 23.8c1.35.8 2.9 1.2 4.5 1.2h50.8c1.6 0 3.15-.45 4.5-1.2z"/><path fill="#ffba00" d="m73.4 26.5-12.7-22c-.8-1.4-1.95-2.5-3.3-3.3l-13.75 23.8 16.15 28h27.45c0-1.55-.4-3.1-1.2-4.5z"/></g></g>
</g>
<g transform="translate(394.0 402.7)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:5.2s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-20 -20) scale(0.314964)"><path fill="#E01E5A" d="M27.2 80c0 7.3-5.9 13.2-13.2 13.2C6.7 93.2.8 87.3.8 80c0-7.3 5.9-13.2 13.2-13.2h13.2V80zm6.6 0c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2v33c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V80z"/><path fill="#36C5F0" d="M47 27c-7.3 0-13.2-5.9-13.2-13.2C33.8 6.5 39.7.6 47 .6c7.3 0 13.2 5.9 13.2 13.2V27H47zm0 6.7c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H13.9C6.6 60.1.7 54.2.7 46.9c0-7.3 5.9-13.2 13.2-13.2H47z"/><path fill="#2EB67D" d="M99.9 46.9c0-7.3 5.9-13.2 13.2-13.2 7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H99.9V46.9zm-6.6 0c0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V13.8C66.9 6.5 72.8.6 80.1.6c7.3 0 13.2 5.9 13.2 13.2v33.1z"/><path fill="#ECB22E" d="M80.1 99.8c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2-7.3 0-13.2-5.9-13.2-13.2V99.8h13.2zm0-6.6c-7.3 0-13.2-5.9-13.2-13.2 0-7.3 5.9-13.2 13.2-13.2h33.1c7.3 0 13.2 5.9 13.2 13.2 0 7.3-5.9 13.2-13.2 13.2H80.1z"/></g></g>
</g>
<g transform="translate(288.0 330.0)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:7.8s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-12 -12)"><rect x="2" y="4" width="20" height="16" rx="2.5" fill="none" stroke="#1E6FD9" stroke-width="2"/><path d="M3 6l9 7 9-7" fill="none" stroke="#1E6FD9" stroke-width="2"/></g></g>
</g>
<g transform="translate(394.0 257.3)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:10.4s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-12 -12)"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" fill="none" stroke="#22C55E" stroke-width="2"/></g></g>
</g>
<g transform="translate(606.0 257.3)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:13.0s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><g transform="translate(-12 -12)"><rect x="3" y="4" width="18" height="17" rx="2.5" fill="none" stroke="#1E6FD9" stroke-width="2"/><path d="M3 9h18M8 2v4M16 2v4" fill="none" stroke="#1E6FD9" stroke-width="2"/></g></g>
</g>
<g transform="translate(797.9 396.0)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:1.3s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/anthropic-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
<g transform="translate(500.0 462.0)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:3.9s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/openai-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
<g transform="translate(202.1 396.0)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:6.5s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/xai-grok-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
<g transform="translate(202.1 264.0)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:9.1s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/google-gemini-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
<g transform="translate(500.0 198.0)" class="tt-node tt-x">
  <circle class="tt-beat" r="30" style="--d:11.7s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/moonshot-kimi-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
<g transform="translate(797.9 264.0)" class="tt-node">
  <circle class="tt-beat" r="30" style="--d:14.3s"/>
  <g class="tt-tile"><rect x="-28" y="-28" width="56" height="56" rx="15" fill="#fff" stroke="#E2E8F0" stroke-width="1"/><image href="{{ asset('images/brand/models/zhipu-zai-mark.svg') }}" x="-11" y="-7.5" width="22" height="22" preserveAspectRatio="xMidYMid meet"/></g>
</g>
</g>
</svg>
  </div>
</div>
