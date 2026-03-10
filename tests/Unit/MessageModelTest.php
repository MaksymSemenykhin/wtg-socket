<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_has_sender_and_recipient_relations(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $message = Message::create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hi',
        ]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertInstanceOf(User::class, $message->recipient);
        $this->assertSame($sender->id, $message->sender->id);
        $this->assertSame($recipient->id, $message->recipient->id);
    }

    public function test_fillable_attributes(): void
    {
        $message = new Message;
        $this->assertEquals(['sender_id', 'recipient_id', 'body', 'read_at'], $message->getFillable());
    }
}
