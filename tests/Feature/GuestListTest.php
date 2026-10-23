<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GuestListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_listed_alphabetically_and_all_can_be_shown_on_one_page(): void
    {
        [$user, $event] = $this->event();

        Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Zulu Guest',
            'phone_number' => '0712345678',
        ]);

        Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Alpha Guest',
            'phone_number' => '0767654321',
        ]);

        $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'per_page' => 'all']))
            ->assertOk()
            ->assertViewHas('guests', function ($guests) {
                return $guests instanceof Collection
                    && $guests->count() === 2
                    && $guests->first()->full_name === 'Alpha Guest'
                    && $guests->last()->full_name === 'Zulu Guest';
            });
    }

    private function event(): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host@example.com',
            'password' => bcrypt('password123'),
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'slug' => 'wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        return [$user, $event];
    }
}
