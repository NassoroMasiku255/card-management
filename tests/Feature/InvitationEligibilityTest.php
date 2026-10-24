<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_card_minimums_are_used_when_an_event_has_no_thresholds(): void
    {
        [, $event] = $this->makeEvent();

        $this->assertSame(70000.0, $event->single_card_minimum);
        $this->assertSame(100000.0, $event->double_card_minimum);
        $this->assertSame('single', $event->cardTypeForAmount(69999));
        $this->assertSame('single', $event->cardTypeForAmount(70000));
        $this->assertSame('double', $event->cardTypeForAmount(100000));
    }

    public function test_invitation_list_only_includes_guests_who_meet_the_paid_minimum(): void
    {
        [$user, $event] = $this->makeEvent();

        $this->addGuest($event, 'Unpaid Guest', 0);
        $this->addGuest($event, 'Partial Guest', 69999);
        $this->addGuest($event, 'Single Guest', 70000);
        $this->addGuest($event, 'Double Guest', 100000);

        $response = $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Single Guest')
            ->assertSee('Double Guest')
            ->assertDontSee('Unpaid Guest')
            ->assertDontSee('Partial Guest');

        $this->assertSame(
            ['Double Guest', 'Single Guest'],
            $this->invitationNames($response)
        );

        $response->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2 && $stats['pending'] === 2);
    }

    public function test_invitation_list_supports_search_send_status_rsvp_filters_and_alphabetical_order(): void
    {
        [$user, $event] = $this->makeEvent();

        $this->addGuest($event, 'Zulu Guest', 100000);
        $this->addGuest($event, 'Alpha Guest', 80000, invitation: [
            'send_status' => 'sent',
            'rsvp_status' => 'attending',
        ]);
        $this->addGuest($event, 'Beta Guest', 120000, invitation: [
            'send_status' => 'failed',
            'rsvp_status' => 'not_attending',
        ]);

        $response = $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk();

        $this->assertSame(
            ['Alpha Guest', 'Beta Guest', 'Zulu Guest'],
            $this->invitationNames($response)
        );

        $search = $this->actingAs($user)
            ->get(route('events.invitations.index', ['event' => $event, 'search' => 'Beta']))
            ->assertOk();
        $this->assertSame(['Beta Guest'], $this->invitationNames($search));

        $status = $this->actingAs($user)
            ->get(route('events.invitations.index', ['event' => $event, 'send_status' => 'failed']))
            ->assertOk();
        $this->assertSame(['Beta Guest'], $this->invitationNames($status));

        $rsvp = $this->actingAs($user)
            ->get(route('events.invitations.index', ['event' => $event, 'rsvp' => 'attending']))
            ->assertOk();
        $this->assertSame(['Alpha Guest'], $this->invitationNames($rsvp));
    }

    public function test_event_card_minimums_can_be_saved_and_drive_invitation_card_type(): void
    {
        [$user] = $this->makeEvent();

        $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Custom Threshold Wedding',
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
            'card_template' => 'elegant',
            'single_card_min_amount' => 150000,
            'double_card_min_amount' => 300000,
        ])->assertRedirect();

        $event = Event::where('name', 'Custom Threshold Wedding')->firstOrFail();

        $this->assertSame(150000.0, $event->single_card_minimum);
        $this->assertSame(300000.0, $event->double_card_minimum);
        $this->assertSame('single', $event->cardTypeForAmount(299999));
        $this->assertSame('double', $event->cardTypeForAmount(300000));

        [, $guest] = $this->addGuest($event, 'Couple Guest', 300000, pledged: 150000);

        $this->actingAs($user)
            ->get(route('events.invitations.card', [$event, $guest]))
            ->assertOk()
            ->assertSee('Couple Invitation')
            ->assertDontSee('Single Invitation');
    }

    public function test_guest_list_card_type_filter_uses_pledged_amount_not_paid_amount(): void
    {
        [$user, $event] = $this->makeEvent();

        $this->addGuest($event, 'Pledged Single Guest', 100000, pledged: 80000);
        $this->addGuest($event, 'Pledged Double Guest', 0, pledged: 100000);

        $single = $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'card_type' => 'single']))
            ->assertOk();
        $this->assertSame(['Pledged Single Guest'], $this->guestNames($single));

        $double = $this->actingAs($user)
            ->get(route('events.guests.index', ['event' => $event, 'card_type' => 'double']))
            ->assertOk();
        $this->assertSame(['Pledged Double Guest'], $this->guestNames($double));
    }

    public function test_ineligible_guests_are_rejected_by_send_and_pending_endpoints(): void
    {
        [$user, $event] = $this->makeEvent();

        [, $ineligible] = $this->addGuest($event, 'Ineligible Guest', 69999);
        $this->addGuest($event, 'Eligible Guest', 70000);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $ineligible]), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertSame('pending', $ineligible->fresh()->send_status);

        $pending = $this->actingAs($user)
            ->getJson(route('events.invitations.pending', $event))
            ->assertOk()
            ->assertJsonCount(1, 'invitations')
            ->assertJsonPath('invitations.0.guest_name', 'Eligible Guest');
    }

    public function test_invitation_export_includes_eligible_guests_with_their_qr_barcodes(): void
    {
        [$user, $event] = $this->makeEvent();

        [, $alpha] = $this->addGuest($event, 'Alpha Guest', 70000, invitation: [
            'send_status' => 'sent',
            'rsvp_status' => 'attending',
        ]);
        [, $zulu] = $this->addGuest($event, 'Zulu Guest', 100000);
        $this->addGuest($event, 'Unpaid Guest', 0);

        $response = $this->actingAs($user)
            ->get(route('events.invitations.export', $event))
            ->assertOk()
            ->assertSee('Invitation Guest List')
            ->assertSee('Print / Save PDF')
            ->assertSee('Scan at entrance')
            ->assertSee('Alpha Guest')
            ->assertSee('Zulu Guest')
            ->assertDontSee('Unpaid Guest')
            ->assertSee('data-qr-code="'.$alpha->qr_code_data.'"', false)
            ->assertSee('data-qr-code="'.$zulu->qr_code_data.'"', false);

        $response->assertViewHas('invitations', function ($invitations) {
            return $invitations->pluck('guest.full_name')->all() === ['Alpha Guest', 'Zulu Guest'];
        });
    }

    public function test_invitation_export_respects_the_current_list_filters(): void
    {
        [$user, $event] = $this->makeEvent();

        $this->addGuest($event, 'Sent Guest', 70000, invitation: ['send_status' => 'sent']);
        $this->addGuest($event, 'Failed Guest', 70000, invitation: ['send_status' => 'failed']);
        $this->addGuest($event, 'Pending Guest', 70000);

        $response = $this->actingAs($user)
            ->get(route('events.invitations.export', ['event' => $event, 'send_status' => 'failed']))
            ->assertOk()
            ->assertSee('Failed Guest')
            ->assertDontSee('Sent Guest')
            ->assertDontSee('Pending Guest');

        $response->assertViewHas('invitations', function ($invitations) {
            return $invitations->pluck('guest.full_name')->all() === ['Failed Guest'];
        });
    }

    private function makeEvent(array $attributes = []): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'can_send_whatsapp' => true,
        ]);

        $event = Event::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'slug' => 'wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ], $attributes));

        return [$user, $event];
    }

    private function addGuest(Event $event, string $name, float $paid, float $pledged = 0, array $invitation = []): array
    {
        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => $name,
            'phone_number' => '07'.str_pad((string) random_int(10000000, 99999999), 8, '0'),
            'amount_contributed' => $pledged,
            'paid_amount' => $paid,
            'card_type' => $event->cardTypeForAmount($pledged),
        ]);

        $invitation = Invitation::create(array_merge([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ], $invitation));

        return [$guest, $invitation];
    }

    private function invitationNames($response): array
    {
        return $response->viewData('invitations')
            ->getCollection()
            ->pluck('guest.full_name')
            ->all();
    }

    private function guestNames($response): array
    {
        return $response->viewData('guests')
            ->pluck('full_name')
            ->all();
    }
}
