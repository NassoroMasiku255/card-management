<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppPermissionTest extends TestCase
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
            'services.whatsapp.reminder_template_name' => 'reminder_sw',
            'services.whatsapp.reminder_template_language' => 'sw',
        ]);
    }

    public function test_users_without_permission_cannot_trigger_any_whatsapp_send_endpoint(): void
    {
        Http::fake();

        [$user, $event, $guest, $invitation] = $this->makeInvitation(canSendWhatsApp: false);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.invitations.resend', [$event, $invitation]), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.invitations.sendAll', $event), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('events.invitations.pending', $event))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'invitation',
                'template_name' => 'wedding_invitation',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame('pending', $invitation->fresh()->send_status);
    }

    public function test_users_granted_permission_can_send_invitations_and_guest_messages(): void
    {
        Http::fake(function () {
            static $sequence = 0;
            $sequence++;

            return Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.allowed.'.$sequence]],
            ]);
        });

        [$user, $event, $guest, $invitation] = $this->makeInvitation(canSendWhatsApp: true);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        Http::assertSentCount(2);
    }

    public function test_administrators_can_send_even_when_the_permission_checkbox_is_off(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.admin']],
            ]),
        ]);

        [$admin, $event, , $invitation] = $this->makeInvitation(isAdmin: true);

        $this->actingAs($admin)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_send_controls_are_hidden_from_users_without_permission_but_statuses_remain_visible(): void
    {
        [$user, $event, $guest] = $this->makeInvitation(canSendWhatsApp: false);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertDontSee('WhatsApp sending is')
            ->assertDontSee('view-only')
            ->assertSee('Not sent')
            ->assertDontSee('Send WhatsApp to selected')
            ->assertDontSee('Send Message');

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertDontSee('WhatsApp sending is')
            ->assertDontSee('view-only')
            ->assertSee('Pending')
            ->assertDontSee('Send All Pending')
            ->assertDontSee('Prepare Invitation');
    }

    public function test_send_controls_are_visible_to_users_granted_permission(): void
    {
        [$user, $event] = $this->makeInvitation(canSendWhatsApp: true);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertSee('Send WhatsApp to selected')
            ->assertSee('Send Message');

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Send All Pending')
            ->assertSee('Prepare Invitation');
    }

    public function test_admin_can_grant_and_revoke_whatsapp_permission_from_user_management(): void
    {
        [$admin] = $this->makeInvitation(isAdmin: true);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Message Manager',
                'email' => 'messages@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'can_send_whatsapp' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $manager = User::where('email', 'messages@example.com')->firstOrFail();
        $this->assertTrue($manager->canSendWhatsApp());

        $this->actingAs($admin)
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'password' => '',
                'password_confirmation' => '',
                'can_send_whatsapp' => '0',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($manager->fresh()->canSendWhatsApp());
    }

    private function makeInvitation(bool $canSendWhatsApp = false, bool $isAdmin = false): array
    {
        $user = User::create([
            'name' => 'User '.uniqid(),
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => $isAdmin,
            'can_send_whatsapp' => $canSendWhatsApp,
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Permission Wedding',
            'slug' => 'permission-wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Permission Guest',
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

        return [$user, $event, $guest, $invitation];
    }
}
