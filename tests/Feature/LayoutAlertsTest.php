<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_errors_are_surfaced_in_the_layout(): void
    {
        $this->actingAs($this->user())
            ->from('/events/create')
            ->followingRedirects()
            ->post('/events', ['name' => ''])
            ->assertOk()
            ->assertSee('Please fix the following');
    }

    public function test_info_flash_messages_are_rendered(): void
    {
        $user = $this->user();

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'slug' => 'wedding-' . uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        $this->actingAs($user)
            ->from(route('events.invitations.index', $event))
            ->followingRedirects()
            ->post(route('events.invitations.sendAll', $event))
            ->assertOk()
            ->assertSee('No pending invitations to send.');
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Host',
            'email' => 'host@example.com',
            'password' => bcrypt('password123'),
        ]);
    }
}
