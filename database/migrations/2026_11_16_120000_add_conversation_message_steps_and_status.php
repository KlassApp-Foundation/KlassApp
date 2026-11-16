<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

/**
 * Expand the conversation messages table for Laravel AI 1.x.
 *
 * Adds steps and status and backfills them from tool_calls, tool_results,
 * and approval_state. The old columns stay. Dropping them is a later step
 * and needs a verified backup.
 */
return new class extends AiMigration
{
    public function up(): void
    {
        $table = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        if (! Schema::connection($this->getConnection())->hasTable($table)) {
            return;
        }

        if (! Schema::connection($this->getConnection())->hasColumn($table, 'steps')) {
            Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
                $blueprint->longText('steps')->nullable();
                $blueprint->string('status', 25)->default('completed');
            });
        }

        if (! Schema::connection($this->getConnection())->hasColumn($table, 'tool_calls')) {
            return;
        }

        $this->query($table)->where('role', 'user')->update([
            'steps' => '[]',
            'status' => 'completed',
        ]);

        $this->query($table)
            ->select('conversation_id')
            ->distinct()
            ->orderBy('conversation_id')
            ->chunk(100, function (Collection $conversations) use ($table) {
                foreach ($conversations as $conversation) {
                    $this->backfill($table, $conversation->conversation_id);
                }
            });

        $this->query($table)->whereNull('steps')->update([
            'steps' => '[]',
            'status' => 'completed',
        ]);

        Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
            $blueprint->text('tool_calls')->nullable()->change();
            $blueprint->text('tool_results')->nullable()->change();
        });
    }

    public function down(): void
    {
        $table = config('ai.conversations.tables.messages', 'agent_conversation_messages');

        if (! Schema::connection($this->getConnection())->hasTable($table)) {
            return;
        }

        if (Schema::connection($this->getConnection())->hasColumn($table, 'steps')) {
            Schema::connection($this->getConnection())->table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['steps', 'status']);
            });
        }
    }

    protected function backfill(string $table, string $conversationId): void
    {
        $rows = $this->query($table)
            ->where('conversation_id', $conversationId)
            ->where('role', 'assistant')
            ->orderBy('id')
            ->get();

        $results = $rows->flatMap(fn (object $row) => $this->decoded($row->tool_results))->keyBy('id');

        foreach ($rows as $row) {
            $meta = $this->decoded($row->meta);
            $pending = $this->decoded($row->approval_state)['pending'] ?? [];
            $pending = is_array($pending) ? $pending : [];

            $calls = collect($this->decoded($row->tool_calls))
                ->map(function (array $call) use ($results, $pending) {
                    $id = (string) ($call['id'] ?? '');

                    if ($id !== '' && $results->has($id)) {
                        $result = $results[$id];
                        $call['result'] = $result['result'] ?? null;

                        if (! empty($result['denied'])) {
                            $call['denied'] = true;
                        }

                        if (! empty($result['failed'])) {
                            $call['failed'] = true;
                        }

                        return $call;
                    }

                    if ($id !== '' && array_key_exists($id, $pending)) {
                        $reason = $pending[$id];
                        $call['approval_reason'] = is_string($reason) ? $reason : null;
                    }

                    return $call;
                })
                ->values()
                ->all();

            $content = (string) $row->content;
            $steps = $calls !== [] && $content !== ''
                ? [$this->step('', $calls), $this->step($content, [], (string) ($meta['reasoning'] ?? ''))]
                : [$this->step($content, $calls, (string) ($meta['reasoning'] ?? ''))];

            unset($meta['provider_steps'], $meta['provider_content_blocks'], $meta['reasoning']);

            $this->query($table)->where('id', $row->id)->update([
                'steps' => json_encode($steps),
                'meta' => json_encode($meta),
                'status' => $pending !== [] ? 'paused' : 'completed',
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $calls
     * @return array<string, mixed>
     */
    protected function step(string $content, array $calls = [], string $reasoning = ''): array
    {
        return [
            'content' => $content,
            'tool_calls' => $calls,
            'reasoning' => $reasoning,
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function decoded(?string $json): array
    {
        return is_array($decoded = json_decode($json ?? '', true)) ? $decoded : [];
    }

    protected function query(string $table): Builder
    {
        return DB::connection($this->getConnection())->table($table);
    }
};
