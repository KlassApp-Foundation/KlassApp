{{-- SPDX-License-Identifier: MIT --}}
{{--
  Pre-onboarding product demo (3 scenes, auto-loop).
  Scene 3 connectors: WhatsApp is live; Google sign-in exists; Slack/Notion are
  aspirational/marketing only — labeled "Coming soon", not claimed as shipped.
--}}
<link rel="stylesheet" href="{{ asset('css/empty-state-product-demo.css') }}">
<div class="es-demo"
     id="empty-state-product-demo"
     data-testid="empty-state-product-demo"
     data-es-demo
     role="region"
     aria-label="Product preview: WhatsApp, Toshi, and future connections">
    <div class="es-demo-static" data-testid="empty-state-demo-static" hidden>
        <p class="es-demo-static-title">See what KlassApp can do</p>
        <p class="es-demo-static-text">Parents message on WhatsApp. Toshi helps finish admin tasks. More connectors are on the roadmap.</p>
    </div>

    <div class="es-demo-stage" data-es-demo-stage>
        {{-- Scene 1: WhatsApp phone --}}
        <div class="es-demo-scene is-active" data-scene="0" data-testid="es-demo-scene-whatsapp" aria-hidden="false">
            <div class="es-demo-copy">
                <p class="es-demo-kicker">WhatsApp</p>
                <h3 class="es-demo-headline">Parents get answers without calling the office</h3>
                <p class="es-demo-sub">Attendance, fees, and results — right in the chat they already use.</p>
            </div>
            <div class="es-phone" aria-hidden="true">
                <div class="es-phone-notch"></div>
                <div class="es-wa-header">
                    <span class="es-wa-avatar">K</span>
                    <div>
                        <div class="es-wa-name">KlassApp School</div>
                        <div class="es-wa-status">online</div>
                    </div>
                </div>
                <div class="es-wa-thread">
                    <div class="es-wa-bubble es-wa-in es-msg" data-step="1">Hi — is Amina in class today?</div>
                    <div class="es-wa-bubble es-wa-out es-msg" data-step="2">Yes ✅ Amina is present (P.5A).</div>
                    <div class="es-wa-bubble es-wa-in es-msg" data-step="3">And school fees balance?</div>
                    <div class="es-wa-bubble es-wa-out es-msg" data-step="4">Balance: UGX 45,000. Last paid 2 Aug.</div>
                    <div class="es-wa-bubble es-wa-out es-msg" data-step="5">Term 2 mid results: Math 78 · Eng 82 · Sci 75</div>
                </div>
            </div>
        </div>

        {{-- Scene 2: Toshi browser --}}
        <div class="es-demo-scene" data-scene="1" data-testid="es-demo-scene-toshi" aria-hidden="true">
            <div class="es-demo-copy">
                <p class="es-demo-kicker">Toshi</p>
                <h3 class="es-demo-headline">Ask once — watch the admin work finish</h3>
                <p class="es-demo-sub">Toshi adds students, drafts reports, and shows the result in your dashboard.</p>
            </div>
            <div class="es-browser" aria-hidden="true">
                <div class="es-browser-chrome">
                    <span class="es-dot es-dot-r"></span>
                    <span class="es-dot es-dot-y"></span>
                    <span class="es-dot es-dot-g"></span>
                    <span class="es-browser-url">klassapp.xyz/admin/dashboard</span>
                </div>
                <div class="es-browser-body">
                    <div class="es-toshi-panel">
                        <div class="es-toshi-title">Toshi</div>
                        <div class="es-chat-bubble es-chat-user es-msg" data-step="1">Add a new student: Okello James, P.4</div>
                        <div class="es-chat-bubble es-chat-bot es-msg" data-step="2">Creating student profile…</div>
                        <div class="es-chat-bubble es-chat-bot es-msg" data-step="3">Done — Okello James added to P.4.</div>
                    </div>
                    <div class="es-result-panel">
                        <div class="es-result-label">Students</div>
                        <div class="es-table">
                            <div class="es-tr es-tr-head"><span>Name</span><span>Class</span></div>
                            <div class="es-tr"><span>Nakaayi Ruth</span><span>P.5</span></div>
                            <div class="es-tr es-tr-new es-msg" data-step="4"><span>Okello James</span><span>P.4</span></div>
                        </div>
                        <div class="es-pdf es-msg" data-step="5">
                            <span class="es-pdf-icon">PDF</span>
                            <span>Term report ready</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Scene 3: connectors (aspirational where noted) --}}
        <div class="es-demo-scene" data-scene="2" data-testid="es-demo-scene-connectors" aria-hidden="true">
            <div class="es-demo-copy">
                <p class="es-demo-kicker">Connections</p>
                <h3 class="es-demo-headline">Meet schools where they already work</h3>
                <p class="es-demo-sub">WhatsApp is live today. Google sign-in works. More apps are on the roadmap — shown as coming soon, not as shipping features.</p>
            </div>
            <div class="es-connect" aria-hidden="true">
                <div class="es-connect-hub">
                    <div class="es-hub-core">Toshi</div>
                    <div class="es-hub-ray es-hub-ray-1"></div>
                    <div class="es-hub-ray es-hub-ray-2"></div>
                    <div class="es-hub-ray es-hub-ray-3"></div>
                    <div class="es-hub-node es-hub-wa es-msg" data-step="1">
                        <strong>WhatsApp</strong>
                        <span class="es-badge es-badge-live">Live</span>
                    </div>
                    <div class="es-hub-node es-hub-google es-msg" data-step="2">
                        <strong>Google</strong>
                        <span class="es-badge es-badge-live">Sign-in</span>
                    </div>
                    <div class="es-hub-node es-hub-slack es-msg" data-step="3">
                        <strong>Slack</strong>
                        <span class="es-badge es-badge-soon">Coming soon</span>
                    </div>
                    <div class="es-hub-node es-hub-notion es-msg" data-step="4">
                        <strong>Notion</strong>
                        <span class="es-badge es-badge-soon">Coming soon</span>
                    </div>
                </div>
                <p class="es-connect-disclaimer" data-testid="es-demo-connectors-disclaimer">
                    Slack and Notion tiles are aspirational previews — not available integrations yet.
                </p>
            </div>
        </div>
    </div>

    <div class="es-demo-dots" data-es-demo-dots aria-hidden="true">
        <span class="es-dot-nav is-active" data-dot="0"></span>
        <span class="es-dot-nav" data-dot="1"></span>
        <span class="es-dot-nav" data-dot="2"></span>
    </div>
</div>
