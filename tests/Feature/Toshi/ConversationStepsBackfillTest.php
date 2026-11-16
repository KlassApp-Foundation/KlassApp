<?php

namespace Tests\Feature\Toshi;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConversationStepsBackfillTest extends TestCase
{
    public function test_backfill_adds_steps_and_keeps_the_old_columns(): void
    {
        config([
            'database.connections.conversation_backfill' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'ai.conversations.connection' => 'conversation_backfill',
            'ai.conversations.tables.messages' => 'agent_conversation_messages',
        ]);

        DB::purge('conversation_backfill');

        Schema::connection('conversation_backfill')->create('agent_conversation_messages', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('conversation_id', 36);
            $table->string('role', 25);
            $table->text('content');
            $table->text('tool_calls');
            $table->text('tool_results');
            $table->text('meta');
            $table->text('approval_state')->nullable();
        });

        DB::connection('conversation_backfill')->table('agent_conversation_messages')->insert([
            [
                'id' => 'user-1',
                'conversation_id' => 'conv-1',
                'role' => 'user',
                'content' => 'Delete the file',
                'tool_calls' => '[]',
                'tool_results' => '[]',
                'meta' => '[]',
                'approval_state' => null,
            ],
            [
                'id' => 'assistant-1',
                'conversation_id' => 'conv-1',
                'role' => 'assistant',
                'content' => '',
                'tool_calls' => json_encode([
                    ['id' => 'call_1', 'name' => 'delete_file', 'arguments' => ['path' => 'a']],
                ]),
                'tool_results' => '[]',
                'meta' => json_encode(['reasoning' => 'needs a person']),
                'approval_state' => json_encode(['pending' => ['call_1' => 'Destructive.']]),
            ],
        ]);

        $migration = require database_path('migrations/2026_11_16_120000_add_conversation_message_steps_and_status.php');
        $migration->up();

        $assistant = DB::connection('conversation_backfill')->table('agent_conversation_messages')->where('id', 'assistant-1')->first();
        $steps = json_decode($assistant->steps, true);

        $this->assertSame('paused', $assistant->status);
        $this->assertSame('call_1', $steps[0]['tool_calls'][0]['id']);
        $this->assertSame('Destructive.', $steps[0]['tool_calls'][0]['approval_reason']);
        $this->assertArrayNotHasKey('result', $steps[0]['tool_calls'][0]);
        $this->assertSame('needs a person', $steps[0]['reasoning']);
        $this->assertNotNull($assistant->tool_calls);
        $this->assertNotNull($assistant->approval_state);
        $this->assertTrue(Schema::connection('conversation_backfill')->hasColumn('agent_conversation_messages', 'tool_calls'));
        $this->assertTrue(Schema::connection('conversation_backfill')->hasColumn('agent_conversation_messages', 'approval_state'));

        $user = DB::connection('conversation_backfill')->table('agent_conversation_messages')->where('id', 'user-1')->first();
        $this->assertSame('[]', $user->steps);
        $this->assertSame('completed', $user->status);

        DB::connection('conversation_backfill')->table('agent_conversation_messages')->insert([
            'id' => 'assistant-2',
            'conversation_id' => 'conv-1',
            'role' => 'assistant',
            'content' => 'Done.',
            'meta' => '[]',
            'steps' => '[]',
            'status' => 'completed',
        ]);

        $this->assertNull(
            DB::connection('conversation_backfill')->table('agent_conversation_messages')->where('id', 'assistant-2')->value('tool_calls')
        );
    }
}
