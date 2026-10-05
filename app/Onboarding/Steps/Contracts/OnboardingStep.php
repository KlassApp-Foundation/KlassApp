<?php

namespace App\Onboarding\Steps\Contracts;

use App\Models\School;
use InvalidArgumentException;

interface OnboardingStep
{
    public function key(): string;

    public function applies(School $school): bool;

    public function question(): string;

    /** @return string text|choice|list|phone_otp|plan|structure|skipable_list */
    public function inputType(): string;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(School $school): array;

    public function normalize(mixed $raw): mixed;

    /**
     * @throws InvalidArgumentException
     */
    public function validate(School $school, mixed $normalized): void;

    public function save(School $school, mixed $normalized, ?int $userId = null): void;

    public function isComplete(School $school, ?int $userId = null): bool;

    public function required(): bool;

    /**
     * What save() would do with this answer, computed read-only.
     *
     * The step later must be feedable from a file as well as from chat, so
     * both preview and save accept structured answers; neither may depend on
     * a conversation, a session, or Livewire state.
     *
     * @param  mixed  $normalized  already-normalized structured answer (same shape save() takes)
     * @return array{action: 'noop'|'skip'|'change', summary: string, rows: list<array{label: string, status: 'create'|'update'|'already_present'|'skip', detail: ?string}>}
     * @throws InvalidArgumentException when validate() rejects the answer
     */
    public function preview(School $school, mixed $normalized, ?int $userId = null): array;

    /**
     * Structured save that reports what it did. Idempotent: submitting the
     * same answer twice must not duplicate rows or fail on already-present
     * data — the rerun reports the existing rows as skipped.
     *
     * @return array{created: list<mixed>, skipped: list<array{label: string, reason: string}>}
     */
    public function saveAndReport(School $school, mixed $normalized, ?int $userId = null): array;
}
