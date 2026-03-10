<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login', absolute: false));
    }

    public function test_guest_cannot_store_message(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('messages.store'), [
            'recipient_id' => $user->id,
            'body' => 'Hello',
        ]);

        $response->assertStatus(401);
    }

    public function test_guest_cannot_get_messages_history(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/messages/{$user->id}");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_see_dashboard_with_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        $this->assertCount(1, $users);
        $this->assertSame(0, $users->first()->unread_count);
    }

    public function test_authenticated_user_can_store_message(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $response = $this->actingAs($sender)->postJson(route('messages.store'), [
            'recipient_id' => $recipient->id,
            'body' => 'Hello there',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('message.body', 'Hello there');
        $response->assertJsonPath('message.sender_id', $sender->id);
        $response->assertJsonPath('message.recipient_id', $recipient->id);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => 'Hello there',
        ]);
    }

    public function test_authenticated_user_can_get_messages_history(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $user->id,
            'recipient_id' => $other->id,
            'body' => 'Hi',
        ]);

        $response = $this->actingAs($user)->getJson("/messages/{$other->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'messages');
        $response->assertJsonPath('messages.0.body', 'Hi');
        $response->assertJsonStructure(['messages', 'unread_counts']);
    }

    public function test_get_messages_marks_incoming_as_read_and_returns_unread_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $other->id,
            'recipient_id' => $user->id,
            'body' => 'To you',
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)->getJson("/messages/{$other->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('messages.0.body', 'To you');
        $this->assertNotNull(Message::where('recipient_id', $user->id)->where('sender_id', $other->id)->first()->read_at);
        $unreadCounts = $response->json('unread_counts');
        $this->assertIsArray($unreadCounts);
        $this->assertSame(0, $unreadCounts[$other->id] ?? 0);
    }

    public function test_cannot_send_message_to_self(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('messages.store'), [
            'recipient_id' => $user->id,
            'body' => 'No',
        ]);

        $response->assertStatus(422);
    }
}
