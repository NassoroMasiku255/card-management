<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventCountdownCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_countdown_card_shows_days_remaining_for_the_event(): void
    {
        [$user, $event] = $this->event(now()->addDays(10));

        $this->actingAs($user)
            ->get(route('events.countdown-card', $event))
            ->assertOk()
            ->assertSee('Countdown Card')
            ->assertSee('Share to WhatsApp')
            ->assertSee($event->name)
            ->assertViewHas('daysRemaining', 10);
    }

    public function test_countdown_card_is_not_available_to_another_user(): void
    {
        [, $event] = $this->event(now()->addDays(5));

        $other = User::create([
            'name' => 'Other',
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($other)
            ->get(route('events.countdown-card', $event))
            ->assertForbidden();
    }

    private function event($date): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host@example.com',
            'password' => bcrypt('password123'),
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding Celebration',
            'slug' => 'wedding-'.uniqid(),
            'event_date' => $date->toDateString(),
            'event_time' => '16:00',
            'location' => 'Dar es Salaam',
        ]);

        return [$user, $event];
    }
}
