<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.api_url' => 'https://graph.facebook.com/v25.0',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.template_name' => 'wedding_invitation',
            'services.whatsapp.template_language' => 'en_US',
        ]);
    }

    public function test_public_invitation_page_shows_event_guest_and_card_download_details(): void
    {
        [$event, $guest, $invitation] = $this->makeInvitation();

        $this->get(route('invitation.public', $invitation->qr_code_data))
            ->assertOk()
            ->assertSee($event->name)
            ->assertSee($guest->full_name)
            ->assertSee($guest->unique_id)
            ->assertSee('Download / Print Card')
            ->assertSee('Will you be joining us?')
            ->assertSee('Your QR code')
            ->assertSee('data-qr="'.$invitation->qr_code_data.'"', false);
    }

    public function test_public_card_can_be_opened_and_links_back_to_the_public_invitation(): void
    {
        [$event, $guest, $invitation] = $this->makeInvitation();

        $this->get(route('invitation.public.card', $invitation->qr_code_data))
            ->assertOk()
            ->assertSee('Download PNG')
            ->assertSee($guest->full_name)
            ->assertSee(route('invitation.public', $invitation->qr_code_data));
    }

    public function test_guest_can_rsvp_from_the_public_invitation_page(): void
    {
        [, , $invitation] = $this->makeInvitation();

        $this->post(route('invitation.public.rsvp', $invitation->qr_code_data), [
            'rsvp_status' => 'attending',
        ])
            ->assertRedirect(route('invitation.public', $invitation->qr_code_data))
            ->assertSessionHas('success');

        $invitation->refresh();

        $this->assertSame('attending', $invitation->rsvp_status);
        $this->assertNotNull($invitation->rsvp_responded_at);
    }

    public function test_public_rsvp_rejects_invalid_statuses(): void
    {
        [, , $invitation] = $this->makeInvitation();

        $this->post(route('invitation.public.rsvp', $invitation->qr_code_data), [
            'rsvp_status' => 'maybe',
        ])->assertSessionHasErrors('rsvp_status');
    }

    public function test_whatsapp_invitation_body_includes_the_public_invitation_link(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.invitation']],
            ]),
        ]);

        [, , $invitation] = $this->makeInvitation();

        $this->assertTrue(app(WhatsAppService::class)->sendInvitation($invitation));

        Http::assertSent(function ($request) use ($invitation) {
            $parameters = $request->data()['template']['components'][0]['parameters'];

            return count($parameters) === 4
                && $parameters[3]['text'] === route('invitation.public', $invitation->qr_code_data);
        });
    }

    public function test_invitation_send_uses_the_template_name_selected_in_the_dialog(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.selected-template']],
            ]),
        ]);

        [$event, , $invitation] = $this->makeInvitation();

        $this->actingAs($event->user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [
                'template_name' => 'wedding_invitation_premium',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSent(function ($request) {
            return $request->data()['template']['name'] === 'wedding_invitation_premium'
                && count($request->data()['template']['components'][0]['parameters']) === 4;
        });
    }

    private function makeInvitation(array $eventAttributes = []): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'can_send_whatsapp' => true,
        ]);

        $event = Event::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Public Wedding',
            'slug' => 'public-wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'event_time' => '16:00',
            'location' => 'Dar es Salaam',
            'hall' => 'Main Hall',
        ], $eventAttributes));

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Public Guest',
            'phone_number' => '0712345678',
            'amount_contributed' => 100000,
            'paid_amount' => 100000,
            'card_type' => 'double',
        ]);

        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ]);

        return [$event, $guest, $invitation];
    }
}
