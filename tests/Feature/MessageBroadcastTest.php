<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MessageBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_sent_event_is_dispatched_when_message_stored_via_api(): void
    {
        Event::fake([MessageSent::class]);

        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => 'Test',
        ]);

        Event::assertDispatched(MessageSent::class, function (MessageSent $event) use ($recipient) {
            return $event->message->recipient_id === $recipient->id && $event->message->body === 'Test';
        });
    }
}
