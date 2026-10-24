<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvitationCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        config([
            'services.whatsapp.api_url' => 'https://graph.facebook.com/v25.0',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.template_name' => 'wedding_invitation',
            'services.whatsapp.template_language' => 'en_US',
            'services.whatsapp.default_image_url' => 'https://example.com/default.jpg',
        ]);
    }

    public function test_guest_card_is_uploaded_and_used_as_the_whatsapp_header(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.card']],
            ]),
        ]);

        [$user, $event, $guest, $invitation] = $this->makeInvitation();

        $response = $this->actingAs($user)->post(
            route('events.invitations.send', [$event, $invitation]),
            ['card_image' => UploadedFile::fake()->image('guest-card.jpg', 800, 1200)],
            ['Accept' => 'application/json']
        );

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('invitation.send_status', 'sent')
            ->assertJsonPath('invitation.whatsapp_status.label', 'Accepted');

        $invitation->refresh();

        $this->assertNotNull($invitation->card_image_path);
        Storage::disk('public')->assertExists($invitation->card_image_path);
        $this->assertSame(asset('storage/'.$invitation->card_image_path), $invitation->card_image_url);

        Http::assertSent(function ($request) use ($invitation) {
            $components = $request->data()['template']['components'];

            return $components[0]['type'] === 'header'
                && $components[0]['parameters'][0]['image']['link'] === asset('storage/'.$invitation->card_image_path);
        });
    }

    public function test_pending_endpoint_lists_every_pending_invitation_with_its_card_state(): void
    {
        [$user, $event, $guest, $invitation] = $this->makeInvitation('Zulu Guest');

        $invitation->update(['card_image_path' => 'invitation-cards/'.$event->id.'/zulu.jpg']);

        $alpha = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Alpha Guest',
            'phone_number' => '0767654321',
        ]);

        Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $alpha->id,
            'qr_code_data' => $alpha->unique_id,
        ]);

        $sentGuest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Middle Guest',
            'phone_number' => '0755123456',
        ]);

        Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $sentGuest->id,
            'qr_code_data' => $sentGuest->unique_id,
            'send_status' => 'sent',
        ]);

        $this->actingAs($user)
            ->getJson(route('events.invitations.pending', $event))
            ->assertOk()
            ->assertJsonCount(2, 'invitations')
            ->assertJsonPath('invitations.0.guest_name', 'Alpha Guest')
            ->assertJsonPath('invitations.1.guest_name', 'Zulu Guest')
            ->assertJsonPath('invitations.1.card_image_url', asset('storage/invitation-cards/'.$event->id.'/zulu.jpg'));
    }

    public function test_invitation_page_has_card_upload_and_message_preview_controls(): void
    {
        [$user, $event] = $this->makeInvitation();

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Invitation card image')
            ->assertSee('WhatsApp message preview')
            ->assertSee('prepare-invitation-btn', false)
            ->assertSee('Send All Pending');
    }

    private function makeInvitation(string $guestName = 'Jane Doe'): array
    {
        $user = User::create([
            'name' => 'Host',
            'email' => 'host-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'slug' => 'wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => $guestName,
            'phone_number' => '0712345678',
        ]);

        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ]);

        return [$user, $event, $guest, $invitation];
    }
}
