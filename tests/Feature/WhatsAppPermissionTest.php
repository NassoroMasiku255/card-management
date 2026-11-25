<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

        [$user, $event, $guest, $invitation] = $this->makeInvitation(canSendWhatsApp: true, canSendSms: true);

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
            ->assertSee('Pending')
            ->assertSee('WhatsApp Message')
            ->assertSee('SMS Message')
            ->assertDontSee('Send WhatsApp to selected')
            ->assertDontSee('Send normal SMS')
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
        [$user, $event] = $this->makeInvitation(canSendWhatsApp: true, canSendSms: true);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertSee('Send WhatsApp to selected')
            ->assertSee('Send Message')
            ->assertSee('Send WhatsApp')
            ->assertSee('Send normal SMS')
            ->assertSee('whatsappModal', false)
            ->assertSee('smsModal', false)
            ->assertSee('fa-inbox', false);

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Send All Pending')
            ->assertSee('Prepare Invitation')
            ->assertSee('fab fa-whatsapp', false)
            ->assertSee('fa-inbox', false)
            ->assertSee('Send invitation SMS')
            ->assertDontSee('fa-comment-sms', false);
    }

    public function test_whatsapp_permission_does_not_allow_sms_and_sms_controls_are_hidden(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response([
            'messaging_product' => 'whatsapp',
            'messages' => [['id' => 'wamid.whatsapp-only']],
        ])]);

        [$user, $event, $guest, $invitation] = $this->makeInvitation(canSendWhatsApp: true, canSendSms: false);

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('events.invitations.send-sms', [$event, $invitation]))
            ->assertForbidden();

        $this->assertDatabaseCount('sms_messages', 0);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertSee('Send WhatsApp to selected')
            ->assertSee('id="whatsappModal"', false)
            ->assertDontSee('Send normal SMS')
            ->assertDontSee('id="smsModal"', false);

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Send All Pending')
            ->assertDontSee('Send invitation SMS');
    }

    public function test_sms_permission_does_not_allow_whatsapp_and_whatsapp_controls_are_hidden(): void
    {
        config([
            'services.nextsms.api_url' => 'https://messaging-service.co.tz/api/sms/v2/text/single',
            'services.nextsms.token' => 'test-nextsms-token',
            'services.nextsms.from' => 'HARUSI',
        ]);
        Http::fake(['messaging-service.co.tz/*' => Http::response(['message_id' => 'nextsms-only'], 200)]);

        [$user, $event, $guest, $invitation] = $this->makeInvitation(canSendWhatsApp: false, canSendSms: true);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.invitations.sendAll', $event), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('whatsapp_messages', 0);
        $this->assertSame('pending', $invitation->fresh()->send_status);

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('sms_messages', 1);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertSee('Send normal SMS')
            ->assertSee('id="smsModal"', false)
            ->assertDontSee('Send WhatsApp to selected')
            ->assertDontSee('id="whatsappModal"', false);

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('Send invitation SMS')
            ->assertDontSee('Send All Pending')
            ->assertDontSee('Prepare Invitation');
    }

    public function test_permission_on_one_event_does_not_allow_sending_on_another_event(): void
    {
        Http::fake();

        [$user, $allowedEvent] = $this->makeInvitation(canSendWhatsApp: true, canSendSms: true);
        [, $otherEvent, $otherGuest, $otherInvitation] = $this->makeInvitation(canSendWhatsApp: true, canSendSms: true);

        // The user manages the second event but was not granted sending on it.
        $user->events()->attach($otherEvent->id);

        $this->assertTrue($user->canSendOnEvent($allowedEvent, Event::CHANNEL_WHATSAPP));
        $this->assertTrue($user->canSendOnEvent($allowedEvent, Event::CHANNEL_SMS));
        $this->assertFalse($user->canSendOnEvent($otherEvent, Event::CHANNEL_WHATSAPP));
        $this->assertFalse($user->canSendOnEvent($otherEvent, Event::CHANNEL_SMS));

        $this->actingAs($user)
            ->get(route('events.guests.index', $otherEvent))
            ->assertOk()
            ->assertDontSee('Send WhatsApp to selected')
            ->assertDontSee('Send normal SMS');

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$otherEvent, $otherInvitation]), [])
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$otherEvent, $otherGuest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('events.invitations.send-sms', [$otherEvent, $otherInvitation]))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_event_creator_can_send_whatsapp_and_sms_on_their_new_event(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator)
            ->post(route('events.store'), [
                'name' => 'Creator Wedding',
                'event_date' => now()->addMonth()->toDateString(),
                'location' => 'Dar es Salaam',
                'card_template' => 'royal',
                'card_background_color' => '#f8f9fa',
                'card_text_color' => '#1a1a1a',
                'card_accent_color' => '#8b5cf6',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $event = Event::where('name', 'Creator Wedding')->firstOrFail();

        $this->assertTrue($creator->canSendOnEvent($event, Event::CHANNEL_WHATSAPP));
        $this->assertTrue($creator->canSendOnEvent($event, Event::CHANNEL_SMS));
    }

    public function test_admin_grants_whatsapp_and_sms_per_event_from_user_management(): void
    {
        [$admin, $eventA] = $this->makeInvitation(isAdmin: true);
        [, $eventB] = $this->makeInvitation();
        [, $eventC] = $this->makeInvitation();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Message Manager',
                'email' => 'messages@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'events' => [$eventA->id, $eventB->id],
                'whatsapp_events' => [$eventA->id],
                'sms_events' => [$eventB->id, $eventC->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $manager = User::where('email', 'messages@example.com')->firstOrFail();

        $this->assertTrue($manager->canSendOnEvent($eventA, Event::CHANNEL_WHATSAPP));
        $this->assertFalse($manager->canSendOnEvent($eventA, Event::CHANNEL_SMS));
        $this->assertFalse($manager->canSendOnEvent($eventB, Event::CHANNEL_WHATSAPP));
        $this->assertTrue($manager->canSendOnEvent($eventB, Event::CHANNEL_SMS));

        // A send tick on an event the user was not given access to is ignored.
        $this->assertFalse($manager->canManageEvent($eventC));
        $this->assertFalse($manager->canSendOnEvent($eventC, Event::CHANNEL_SMS));

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $manager))
            ->assertOk()
            ->assertSee('name="whatsapp_events[]"', false)
            ->assertSee('name="sms_events[]"', false)
            ->assertDontSee('name="can_send_whatsapp"', false);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [$eventA->id, $eventB->id],
                'sms_events' => [$eventA->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($manager->canSendOnEvent($eventA, Event::CHANNEL_WHATSAPP));
        $this->assertTrue($manager->canSendOnEvent($eventA, Event::CHANNEL_SMS));
        $this->assertFalse($manager->canSendOnEvent($eventB, Event::CHANNEL_SMS));
        $this->assertTrue($manager->canManageEvent($eventB));

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('SMS · 1 event');
    }

    public function test_migration_keeps_existing_senders_on_all_their_current_events(): void
    {
        $sender = User::factory()->create(['can_send_whatsapp' => true]);
        $viewer = User::factory()->create(['can_send_whatsapp' => false]);
        [, $eventA] = $this->makeInvitation();
        [, $eventB] = $this->makeInvitation();

        $migration = require database_path('migrations/2026_11_22_000002_add_send_permissions_to_event_user.php');
        $migration->down();

        $now = now();
        foreach ([$eventA, $eventB] as $event) {
            foreach ([$sender, $viewer] as $member) {
                DB::table('event_user')->insert([
                    'event_id' => $event->id,
                    'user_id' => $member->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $migration->up();

        foreach ([$eventA, $eventB] as $event) {
            $this->assertTrue($sender->canSendOnEvent($event, Event::CHANNEL_WHATSAPP));
            $this->assertTrue($sender->canSendOnEvent($event, Event::CHANNEL_SMS));
            $this->assertFalse($viewer->canSendOnEvent($event, Event::CHANNEL_WHATSAPP));
            $this->assertFalse($viewer->canSendOnEvent($event, Event::CHANNEL_SMS));
            $this->assertTrue($viewer->canManageEvent($event));
        }
    }

    private function makeInvitation(bool $canSendWhatsApp = false, bool $isAdmin = false, bool $canSendSms = false): array
    {
        $user = User::create([
            'name' => 'User '.uniqid(),
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => $isAdmin,
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Permission Wedding',
            'slug' => 'permission-wedding-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);
        $this->fundEvent($event);

        // Creating an event grants its creator both; set the case under test.
        $user->events()->updateExistingPivot($event->id, [
            'can_send_whatsapp' => $canSendWhatsApp,
            'can_send_sms' => $canSendSms,
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
