<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_event_with_time(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('events.store'), [
            'name' => 'John & Jane Wedding',
            'event_date' => now()->addDays(20)->toDateString(),
            'event_time' => '14:30',
            'location' => 'Serena Hotel',
            'hall' => 'Grand Ballroom',
            'card_template' => 'royal',
            'card_background_color' => '#f8f9fa',
            'card_text_color' => '#1a1a1a',
            'card_accent_color' => '#8b5cf6',
        ]);

        $event = Event::first();
        $this->assertNotNull($event);
        $response->assertRedirect(route('events.show', $event));
        $this->assertSame('14:30', $event->event_time);
        $this->assertSame('#8b5cf6', $event->card_accent_color);
    }

    public function test_user_can_edit_event_with_existing_time(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'John & Jane Wedding',
            'event_date' => now()->addDays(20)->toDateString(),
            'event_time' => '16:00:00',
            'location' => 'Serena Hotel',
            'hall' => 'Grand Ballroom',
            'card_template' => 'floral',
            'card_background_color' => '#fff0f5',
            'card_text_color' => '#2d3748',
            'card_accent_color' => '#ec4899',
            'status' => 'active',
        ]);

        // Edit view displays time formatted as H:i
        $editPage = $this->actingAs($user)->get(route('events.edit', $event));
        $editPage->assertOk();
        $editPage->assertSee('value="16:00"', false);
        $editPage->assertSee('value="#fff0f5"', false);
        $editPage->assertSee('value="#ec4899"', false);

        // Submitting with H:i succeeds
        $response = $this->actingAs($user)->put(route('events.update', $event), [
            'name' => 'John & Jane Wedding Updated',
            'event_date' => now()->addDays(20)->toDateString(),
            'event_time' => '16:00',
            'location' => 'Serena Hotel',
            'hall' => 'Main Hall',
            'card_template' => 'floral',
            'card_background_color' => '#fff0f5',
            'card_text_color' => '#2d3748',
            'card_accent_color' => '#ec4899',
            'status' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertSame('John & Jane Wedding Updated', $event->name);
        $this->assertSame('16:00', $event->event_time);

        // Submitting with H:i:s format also succeeds
        $response2 = $this->actingAs($user)->put(route('events.update', $event), [
            'name' => 'John & Jane Wedding Updated Again',
            'event_date' => now()->addDays(20)->toDateString(),
            'event_time' => '17:30:00',
            'location' => 'Serena Hotel',
            'hall' => 'Main Hall',
            'card_template' => 'modern',
            'card_background_color' => '#ffffff',
            'card_text_color' => '#111827',
            'card_accent_color' => '#3b82f6',
            'status' => 'active',
        ]);

        $response2->assertSessionHasNoErrors();
        $event->refresh();
        $this->assertSame('17:30', $event->event_time);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'event_time' => '17:30']);
        $this->assertSame('#3b82f6', $event->card_accent_color);
    }

    public function test_invitation_card_renders_event_custom_colors(): void
    {
        $user = User::factory()->create();
        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Custom Color Gala',
            'event_date' => now()->addDays(15)->toDateString(),
            'event_time' => '18:00',
            'location' => 'Grand Hall',
            'card_template' => 'elegant',
            'card_background_color' => '#0f172a',
            'card_text_color' => '#f8fafc',
            'card_accent_color' => '#f59e0b',
            'status' => 'active',
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Alice Walker',
            'phone_number' => '+255711222333',
            'amount_contributed' => 100000,
            'paid_amount' => 100000,
            'card_type' => 'double',
            'status' => 'confirmed',
        ]);

        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'send_status' => 'sent',
            'rsvp_status' => 'pending',
            'attendance_status' => 'pending',
            'qr_code_data' => 'GALA-001',
        ]);

        // Card view for guest
        $cardResponse = $this->actingAs($user)->get(route('events.invitations.card', [$event, $guest]));
        $cardResponse->assertOk();
        $cardResponse->assertSee('#0f172a');
        $cardResponse->assertSee('#f8fafc');
        $cardResponse->assertSee('#f59e0b');
        $cardResponse->assertSee('colorDark: "#f8fafc"', false);
        $cardResponse->assertSee('colorLight: "#0f172a"', false);

        // Public invitation card view
        $publicCardResponse = $this->get(route('invitation.public.card', 'GALA-001'));
        $publicCardResponse->assertOk();
        $publicCardResponse->assertSee('#0f172a');
        $publicCardResponse->assertSee('#f8fafc');
        $publicCardResponse->assertSee('#f59e0b');

        // Public invitation page should apply the event colours too
        $publicPageResponse = $this->get(route('invitation.public', 'GALA-001'));
        $publicPageResponse->assertOk();
        $publicPageResponse->assertSee('--invite-bg: #0f172a');
        $publicPageResponse->assertSee('--invite-fg: #f8fafc');
        $publicPageResponse->assertSee('background: var(--invite-bg)');
        $publicPageResponse->assertSee('colorDark: "#f8fafc"', false);
    }
}
