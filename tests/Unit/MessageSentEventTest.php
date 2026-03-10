<?php

namespace Tests\Unit;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageSentEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_broadcasts_on_private_user_channel(): void
    {
        $recipient = User::factory()->create();
        $sender = User::factory()->create();
        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hi',
        ]);
        $message->load(['sender', 'recipient']);

        $event = new MessageSent($message);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-user.'.$recipient->id, $channels[0]->name);
    }

    public function test_event_broadcast_name(): void
    {
        $message = Message::factory()->create();
        $message->load(['sender', 'recipient']);
        $event = new MessageSent($message);

        $this->assertSame('MessageSent', $event->broadcastAs());
    }

    public function test_event_broadcast_payload_contains_message_data(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hello',
        ]);
        $message->load(['sender', 'recipient']);

        $event = new MessageSent($message);
        $data = $event->broadcastWith();

        $this->assertArrayHasKey('message', $data);
        $this->assertSame($message->id, $data['message']['id']);
        $this->assertSame('Hello', $data['message']['body']);
        $this->assertSame($sender->id, $data['message']['sender_id']);
        $this->assertSame($recipient->id, $data['message']['recipient_id']);
    }
}
