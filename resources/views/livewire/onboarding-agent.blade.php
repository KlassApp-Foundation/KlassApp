<div x-data="{ hasText: false }">
    <style>
        @keyframes toshi-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }
    </style>
    <div id="toshi-pill"
         wire:click="show" onclick="this.style.display='none'; document.getElementById('toshi-panel').style.display='flex';"
         class="fixed flex items-center cursor-pointer"
         style="{{ $visible ? 'display: none;' : '' }} bottom: 24px; right: 24px; height: 56px; width: 280px; background: #FFFFFF; border-radius: 28px; box-shadow: 0 4px 24px rgba(0,0,0,0.12); padding: 0 8px 0 8px; gap: 10px; z-index: 9999;">
        <div class="flex items-center justify-center shrink-0" style="width: 38px; height: 38px; border-radius: 50%; background: #0F172A; overflow: hidden;">
            <img src="{{ asset('favicon/klassapp-favicon.svg') }}" style="width: 24px; height: 24px;" alt="KlassApp">
        </div>
        <span style="flex: 1; font-size: 13px; color: #64748B; font-weight: 400; white-space: nowrap;">Ask Toshi anything</span>
        <div class="flex items-center shrink-0" style="height: 38px; padding: 0 14px; background: #0075e3; border-radius: 20px; gap: 6px; color: #FFFFFF; font-size: 13px; font-weight: 600;">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="white"><rect x="1" y="4" width="3" height="6" rx="1"/><rect x="5.5" y="1" width="3" height="12" rx="1"/><rect x="10" y="3" width="3" height="8" rx="1"/></svg> Talk
        </div>
    </div>

    <div id="toshi-panel"
         class="flex flex-col overflow-hidden"
         style="{{ $visible ? 'display: flex;' : 'display: none;' }} position: fixed; z-index: 9999; bottom: 0; right: 24px; width: 373px; height: 457px; border-radius: 16px 16px 0 0; box-shadow: 0 -8px 40px rgba(0,0,0,0.18); background: #FFFFFF; border: 1px solid #E2E8F0; border-bottom: none;">
        <div class="flex items-center shrink-0" style="height: 48px; padding: 0 12px; background: #22C55E; gap: 10px;">
            <div class="flex items-center gap-2.5">
                <div class="flex items-center justify-center shrink-0" style="width: 32px; height: 32px; border-radius: 50%; background: #FFFFFF; overflow: hidden;">
                    <img src="{{ asset('favicon/klassapp-favicon.svg') }}" style="width: 22px; height: 22px;" alt="KlassApp">
                </div>
                <div style="color: #FFFFFF; font-size: 14px; font-weight: 800; line-height: 1.2; font-family: 'Sora', sans-serif; letter-spacing: -0.01em;">KlassApp AI</div>
            </div>
            <div class="flex items-center gap-2" style="margin-left: auto;">
                <button wire:click="maximize"
                        class="flex items-center justify-center"
                        style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Expand">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 5V1h4M9 1h4v4M13 9v4H9M5 13H1V9" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button wire:click="hide"
                        class="flex items-center justify-center"
                        style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Close">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto" style="background: #F8FAFC; padding: 16px; display: flex; flex-direction: column; gap: 12px; min-height: 0;">
            @foreach($messages as $msg)
                @php $isUser = $msg['role'] === 'user'; @endphp
                <div style="display: flex; justify-content: {{ $isUser ? 'flex-end' : 'flex-start' }};">
                    <div style="max-width: 80%;">
                        <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; margin-bottom: 4px; padding: 0 4px;">{{ $isUser ? 'You' : 'KlassApp AI' }}</div>
                        <div style="border-radius: 12px; padding: 10px 14px; font-size: 13px; line-height: 1.5; {{ $isUser ? 'background: #0F172A; color: #FFFFFF;' : 'background: #FFFFFF; color: #1E293B; border: 1px solid #E2E8F0;' }}">
                            @if(!$isUser){!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}@else{!! nl2br(e($msg['text'])) !!}@endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <form wire:submit.prevent="send" class="shrink-0 flex items-center gap-2" style="border-top: 1px solid #E2E8F0; background: #FFFFFF; padding: 10px 12px;">
            <label class="flex items-center justify-center shrink-0 cursor-pointer" style="width: 36px; height: 36px; color: #9CA3AF; font-size: 20px; font-weight: 400; transition: color 0.15s;" onmouseover="this.style.color='#1E6FD9'" onmouseout="this.style.color='#9CA3AF'" title="Upload file">
                +
                <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
            </label>
            <textarea rows="1" wire:model.defer="input" placeholder="Type your message…" id="toshi-input-panel" @input="hasText = $el.value.trim().length > 0; $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();Livewire.find('{{ $__livewire->id() }}').send()}" class="flex-1 resize-none" style="border: none; outline: none; font-size: 14px; background: transparent; color: #1E293B; height: 24px; max-height: 120px; line-height: 1.5; overflow-y: auto;"></textarea>
            <button type="button" x-data="{ listening: false }" @click="
                if (!listening) {
                    listening = true; hasText = true;
                    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    if (!SpeechRecognition) { listening = false; alert('Voice input not supported in this browser'); return; }
                    var recognition = new SpeechRecognition();
                    recognition.lang = 'en-US';
                    recognition.interimResults = false;
                    recognition.onresult = function(event) {
                        var text = event.results[0][0].transcript;
                        var input = document.getElementById('toshi-input-panel');
                        input.value = text; input.dispatchEvent(new Event('input', {bubbles: true}));
                        input.closest('form').querySelector('button[type=submit]').click();
                        listening = false;
                    };
                    recognition.onerror = function() { listening = false; };
                    recognition.onend = function() { listening = false; };
                    recognition.start();
                }
            " class="flex items-center justify-center shrink-0 relative" style="width: 40px; height: 40px; border-radius: 50%; background: #0F172A; border: none; cursor: pointer;">
                <span x-show="listening" class="absolute w-3 h-3 rounded-full" style="background: #22C55E; animation: toshi-pulse 1.4s ease-in-out infinite; top: 4px; right: 4px;"></span>
                <svg x-show="!hasText || listening" class="w-5 h-5" style="color: #FFFFFF;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                <svg x-show="hasText && !listening" class="w-5 h-5" style="color: #FFFFFF;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
            </button>
        </form>
    </div>

    <div id="toshi-modal" onclick="if(event.target===this){this.style.display='none'}"
         style="{{ $maximized ? 'display: flex;' : 'display: none;' }} position: fixed; inset: 0; align-items: center; justify-content: center; background: rgba(0,0,0,0.4); z-index: 99999;">
        <div style="width: 760px; max-width: 90vw; height: 85vh; background: #FFFFFF; border-radius: 16px; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 24px 80px rgba(0,0,0,0.25);"
             onclick="event.stopPropagation()">
            <div class="flex items-center shrink-0" style="height: 56px; padding: 0 20px; background: #22C55E; gap: 10px;">
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center justify-center shrink-0" style="width: 36px; height: 36px; border-radius: 50%; background: #FFFFFF; overflow: hidden;">
                        <img src="{{ asset('favicon/klassapp-favicon.svg') }}" style="width: 24px; height: 24px;" alt="KlassApp">
                    </div>
                <div>
                    <div style="color: #FFFFFF; font-size: 14px; font-weight: 800; line-height: 1.2; font-family: 'Sora', sans-serif; letter-spacing: -0.01em;">KlassApp AI</div>
                </div>
                </div>
                <div class="flex items-center gap-2" style="margin-left: auto;">
                    <button wire:click="restore"
                            class="flex items-center justify-center"
                            style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Restore">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M4 1H1v3M10 1h3v3M10 13h3v-3M4 13H1v-3" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button wire:click="hide"
                            class="flex items-center justify-center"
                            style="width: 28px; height: 28px; border-radius: 6px; background: rgba(255,255,255,0.15); border: none; cursor: pointer;" title="Close">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M1 1l10 10M11 1L1 11" stroke="white" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto" style="background: #F8FAFC; padding: 24px; min-height: 0;">
                <div style="max-width: 640px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px;">
                    @foreach($messages as $msg)
                        @php $isUser = $msg['role'] === 'user'; @endphp
                        <div style="display: flex; justify-content: {{ $isUser ? 'flex-end' : 'flex-start' }};">
                            <div style="max-width: 80%;">
                                <div style="font-size: 10px; font-weight: 600; text-transform: uppercase; color: #94A3B8; margin-bottom: 4px; padding: 0 4px;">{{ $isUser ? 'You' : 'KlassApp AI' }}</div>
                                <div style="border-radius: 12px; padding: 12px 16px; font-size: 14px; line-height: 1.6; {{ $isUser ? 'background: #0F172A; color: #FFFFFF;' : 'background: #FFFFFF; color: #1E293B; border: 1px solid #E2E8F0;' }}">
                                    @if(!$isUser){!! preg_replace('/\*\*(.+?)\*\*/','<strong>$1</strong>',nl2br(e($msg['text']))) !!}@else{!! nl2br(e($msg['text'])) !!}@endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <form wire:submit.prevent="send" class="shrink-0" style="border-top: 1px solid #E2E8F0; background: #FFFFFF; padding: 16px 20px;">
                <div style="max-width: 640px; margin: 0 auto; display: flex; align-items: center; gap: 10px;">
                    <label class="flex items-center justify-center shrink-0 cursor-pointer" style="width: 40px; height: 40px; color: #9CA3AF; font-size: 22px; font-weight: 300; transition: color 0.15s;" onmouseover="this.style.color='#1E6FD9'" onmouseout="this.style.color='#9CA3AF'" title="Upload file">
                        +
                        <input type="file" wire:model="attachment" class="hidden" accept=".csv,.xlsx,.xls,.pdf,.png,.jpg,.jpeg,.docx,.txt">
                    </label>
                    <textarea rows="1" wire:model.defer="input" placeholder="Type your message…" @input="hasText = $el.value.trim().length > 0; $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}))}" class="flex-1 resize-none" style="border: 1px solid #E2E8F0; border-radius: 24px; padding: 12px 16px; font-size: 14px; outline: none; background: #FFFFFF; color: #1E293B; height: 48px; max-height: 150px; line-height: 1.5; overflow-y: auto;"></textarea>
                    <button type="submit" class="flex items-center justify-center shrink-0 relative" style="width: 44px; height: 44px; border-radius: 50%; background: #0F172A; border: none; cursor: pointer;">
                        <svg x-show="!hasText" class="w-5 h-5" style="color: #FFFFFF;" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
                        <svg x-show="hasText" class="w-5 h-5" style="color: #FFFFFF;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m-7 7l7-7 7 7"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
