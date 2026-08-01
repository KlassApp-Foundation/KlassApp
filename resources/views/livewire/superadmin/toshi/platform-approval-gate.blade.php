{{-- SPDX-License-Identifier: MIT --}}
{{-- Platform ops tool-approval review gate (not generic chat bubbles). --}}
<div class="platform-approval-gate" style="background:#FFFCF5; color:#0F172A; padding:1.25rem;">
    @if ($flashStatus)
        <div class="mb-4 rounded border border-gray-300 px-4 py-3" style="background:#ECFDF5; border-color:#A7F3D0;">
            <p class="text-sm font-semibold uppercase tracking-wide" style="color:#065F46;">{{ $flashStatus }}</p>
            @if ($flashMessage)
                <p class="mt-1 text-sm" style="color:#064E3B;">{{ $flashMessage }}</p>
            @endif
        </div>
    @endif

    @error('submit')
        <div class="mb-4 rounded border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $message }}</div>
    @enderror

    @if ($hasPendingApprovals)
        <div class="mb-6 rounded border-2 border-amber-400 px-4 py-3" style="background:#FFFBEB;">
            <p class="text-xs font-bold uppercase tracking-widest" style="color:#92400E;">Waiting on approval</p>
            <h2 class="mt-1 text-xl font-bold" style="color:#0F172A;">Review gate — tool calls paused</h2>
            <p class="mt-1 text-sm" style="color:#78350F;">
                Approve, reject, or edit arguments before PlatformOperationsAgent continues. Mutations do not run until you decide.
            </p>
        </div>

        <div class="space-y-4">
            @foreach ($pendingApprovals as $approval)
                <article wire:key="approval-{{ $approval->id }}" class="rounded border border-gray-300 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tool</p>
                            <h3 class="text-lg font-bold" style="color:#0F172A;">{{ $approval->tool }}</h3>
                        </div>
                        <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700">{{ $approval->id }}</code>
                    </div>

                    @if ($approval->reason)
                        <p class="mt-3 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm" style="color:#78350F;">
                            {{ $approval->reason }}
                        </p>
                    @endif

                    <div class="mt-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Arguments</p>
                        <pre class="mt-1 overflow-x-auto rounded border border-gray-200 bg-gray-50 p-3 text-xs" style="color:#0F172A;">{{ json_encode($approval->arguments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500" for="reject-{{ $approval->id }}">
                                Reject reason (optional)
                            </label>
                            <input
                                id="reject-{{ $approval->id }}"
                                type="text"
                                wire:model="decisionDrafts.{{ $approval->id }}.result"
                                class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                                style="background:#fff; color:#0F172A;"
                                placeholder="Why this should not run"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500" for="edit-{{ $approval->id }}">
                                Edit arguments (JSON) then approve
                            </label>
                            <textarea
                                id="edit-{{ $approval->id }}"
                                wire:model="decisionDrafts.{{ $approval->id }}.arguments_json"
                                rows="5"
                                class="mt-1 w-full rounded border border-gray-300 px-3 py-2 font-mono text-xs"
                                style="background:#fff; color:#0F172A;"
                            ></textarea>
                            @error('decisionDrafts.'.$approval->id.'.arguments_json')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="approve('{{ $approval->id }}')"
                            wire:loading.attr="disabled"
                            class="rounded px-4 py-2 text-sm font-semibold text-white"
                            style="background:#047857;"
                        >
                            Approve
                        </button>
                        <button
                            type="button"
                            wire:click="reject('{{ $approval->id }}')"
                            wire:loading.attr="disabled"
                            class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold"
                            style="color:#0F172A;"
                        >
                            Reject
                        </button>
                        <button
                            type="button"
                            wire:click="editAndApprove('{{ $approval->id }}')"
                            wire:loading.attr="disabled"
                            class="rounded border border-amber-400 px-4 py-2 text-sm font-semibold"
                            style="background:#FFFBEB; color:#92400E;"
                        >
                            Edit &amp; approve
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="rounded border border-gray-300 bg-white px-4 py-6">
            <p class="text-xs font-bold uppercase tracking-widest text-gray-500">No pending approvals</p>
            <p class="mt-1 text-sm text-gray-700">This conversation is not waiting on a review decision.</p>
        </div>
    @endif

    <div class="mt-8">
        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-gray-500">Conversation transcript</p>
        <div class="space-y-2 rounded border border-gray-200 bg-white p-3">
            @forelse ($conversation->messages as $message)
                <div wire:key="msg-{{ $message->id }}" class="border-b border-gray-100 py-2 last:border-0">
                    <p class="text-xs font-semibold uppercase text-gray-500">{{ $message->role }}</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm" style="color:#0F172A;">{{ $message->content ?: '—' }}</p>
                    @if (! empty($message->approval_state['pending']))
                        <p class="mt-1 text-xs font-semibold text-amber-700">Paused — pending approval</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No messages yet.</p>
            @endforelse
        </div>
    </div>
</div>
