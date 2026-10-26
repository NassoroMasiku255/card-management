<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_and_event_pages_render_full_overview(): void
    {
        $user = User::factory()->create();

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Amina & Hassan Wedding',
            'event_date' => now()->addDays(12)->toDateString(),
            'event_time' => '16:00',
            'location' => 'Diamond Jubilee Hall',
            'hall' => 'Main Ballroom',
            'description' => 'A celebration with family and friends.',
            'card_template' => 'elegant',
            'card_accent_color' => '#d4af37',
            'status' => 'active',
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Amina Hassan',
            'phone_number' => '0712345678',
            'amount_contributed' => 150000,
            'paid_amount' => 100000,
            'card_type' => 'double',
            'category' => 'Family',
        ]);

        Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => 'test-qr-code',
            'send_status' => 'read',
            'sent_at' => now(),
            'rsvp_status' => 'attending',
            'attendance_status' => 'attended',
            'checked_in_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Amina &amp; Hassan Wedding', false)
            ->assertSee('Contributions')
            ->assertSee('Recent activity');

        $this->actingAs($user)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee('Amina &amp; Hassan Wedding', false)
            ->assertSee('Invitations delivered');

        $eventResponse = $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Amina &amp; Hassan Wedding', false)
            ->assertSee('Check-in progress')
            ->assertSee('Top contributors');

        $this->assertSame(1, $eventResponse->viewData('stats')['invitations_sent']);
    }
}
