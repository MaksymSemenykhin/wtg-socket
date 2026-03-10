<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private MessageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MessageService;
    }

    public function test_get_users_with_unread_counts_returns_other_users_with_unread_count(): void
    {
        $me = User::factory()->create(['name' => 'Me']);
        $other = User::factory()->create(['name' => 'Other']);
        Message::factory()->create([
            'sender_id' => $other->id,
            'recipient_id' => $me->id,
            'body' => 'Hi',
            'read_at' => null,
        ]);

        $users = $this->service->getUsersWithUnreadCounts($me->id);

        $this->assertCount(1, $users);
        $this->assertSame($other->id, $users->first()->id);
        $this->assertSame(1, $users->first()->unread_count);
    }

    public function test_get_users_with_unread_counts_excludes_current_user(): void
    {
        $me = User::factory()->create();
        User::factory()->create();

        $users = $this->service->getUsersWithUnreadCounts($me->id);

        $this->assertCount(1, $users);
        $this->assertNotContains($me->id, $users->pluck('id'));
    }

    public function test_send_message_creates_and_returns_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $message = $this->service->sendMessage($sender->id, $recipient->id, 'Hello');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($sender->id, $message->sender_id);
        $this->assertSame($recipient->id, $message->recipient_id);
        $this->assertSame('Hello', $message->body);
        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hello',
        ]);
    }

    public function test_send_message_throws_when_sending_to_self(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.cannot_send_to_self'));

        $this->service->sendMessage($user->id, $user->id, 'No');
    }

    public function test_get_conversation_with_unread_counts_marks_messages_as_read(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $other->id,
            'recipient_id' => $me->id,
            'body' => 'Unread',
            'read_at' => null,
        ]);

        $result = $this->service->getConversationWithUnreadCounts($me->id, $other->id);

        $this->assertCount(1, $result['messages']);
        $this->assertArrayHasKey('unread_counts', $result);
        $this->assertSame(0, $result['unread_counts'][$other->id] ?? 0);
        $this->assertNotNull(Message::where('recipient_id', $me->id)->where('sender_id', $other->id)->first()->read_at);
    }

    public function test_get_conversation_with_unread_counts_throws_when_same_user(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(__('messages.cannot_load_conversation_self'));

        $this->service->getConversationWithUnreadCounts($user->id, $user->id);
    }

    public function test_format_message_for_json_returns_expected_structure(): void
    {
        $message = Message::factory()->create(['body' => 'Test body']);
        $message->load(['sender', 'recipient']);

        $data = $this->service->formatMessageForJson($message);

        $this->assertSame($message->id, $data['id']);
        $this->assertSame($message->sender_id, $data['sender_id']);
        $this->assertSame($message->recipient_id, $data['recipient_id']);
        $this->assertSame('Test body', $data['body']);
        $this->assertArrayHasKey('sender', $data);
        $this->assertArrayHasKey('recipient', $data);
        $this->assertArrayHasKey('created_at', $data);
    }
}
