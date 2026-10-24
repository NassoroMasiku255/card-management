<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.api_url' => 'https://graph.facebook.com/v25.0',
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.reminder_template_name' => 'reminder_sw',
            'services.whatsapp.reminder_template_language' => 'sw',
            'services.whatsapp.reminder_language_policy' => 'deterministic',
            'services.whatsapp.reminder_payment_details' => 'NMB: 0712345678 | CRDB: 2010123456',
            'services.whatsapp.webhook_verify_token' => 'verify',
            'services.whatsapp.app_secret' => 'secret',
        ]);
    }

    public function test_reminder_is_sent_with_the_expected_template_parameters_and_saved(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '255712345678', 'wa_id' => '255712345678']],
                'messages' => [['id' => 'wamid.reminder']],
            ]),
        ]);

        [$guest, $invitation] = $this->makeGuestAndInvitation();

        $sent = app(WhatsAppService::class)->sendReminder($guest, $invitation);

        $this->assertTrue($sent);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://graph.facebook.com/v25.0/123456/messages'
                && $body['to'] === '255712345678'
                && $body['template']['name'] === 'reminder_sw'
                && $body['template']['language'] === ['policy' => 'deterministic', 'code' => 'sw']
                && $body['template']['components'][0]['type'] === 'body'
                && $body['template']['components'][0]['parameters'][0]['text'] === 'Jane Doe'
                && $body['template']['components'][0]['parameters'][1]['text'] === 'Mchango 200,000 TZS'
                && $body['template']['components'][0]['parameters'][2]['text'] === 'Wedding'
                && $body['template']['components'][0]['parameters'][3]['text'] === 'NMB: 0712345678 | CRDB: 2010123456';
        });

        $message = WhatsAppMessage::firstOrFail();

        $this->assertSame('wamid.reminder', $message->whatsapp_message_id);
        $this->assertSame('reminder', $message->message_type);
        $this->assertSame(WhatsAppMessage::STATUS_ACCEPTED, $message->status);
        $this->assertSame($guest->id, $message->guest_id);
        $this->assertSame($invitation->id, $message->invitation_id);

        $invitation->refresh();
        $this->assertSame('pending', $invitation->send_status);
        $this->assertNull($invitation->sent_at);
    }

    public function test_guest_list_message_send_returns_json_status_for_ajax(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.ajax']],
            ]),
        ]);

        [$guest, $invitation] = $this->makeGuestAndInvitation();

        $this->actingAs($guest->event->user)
            ->postJson(route('events.guests.send-message', [$guest->event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('guest_id', $guest->id)
            ->assertJsonPath('status.state', 'accepted')
            ->assertJsonPath('status.label', 'Accepted')
            ->assertJsonPath('status.message_type', 'Reminder');
    }

    public function test_template_parameters_are_sanitized(): void
    {
        config([
            'services.whatsapp.reminder_payment_details' => "NMB: 1\nCRDB: 2\tExtra     spaces",
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.sanitized']],
            ]),
        ]);

        [$guest, $invitation] = $this->makeGuestAndInvitation();

        app(WhatsAppService::class)->sendReminder($guest, $invitation);

        Http::assertSent(function ($request) {
            return $request->data()['template']['components'][0]['parameters'][3]['text']
                === 'NMB: 1 CRDB: 2 Extra spaces';
        });
    }

    public function test_delivery_webhook_updates_the_saved_message_and_invitation(): void
    {
        [$guest, $invitation] = $this->makeGuestAndInvitation();

        $message = WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'invitation',
            'to_phone' => '255712345678',
            'template_name' => 'wedding_invitation',
            'language_code' => 'en_US',
            'whatsapp_message_id' => 'wamid.reminder',
            'status' => WhatsAppMessage::STATUS_ACCEPTED,
        ]);

        $invitation->update([
            'whatsapp_message_id' => 'wamid.reminder',
            'send_status' => 'sent',
        ]);

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'statuses' => [[
                            'id' => 'wamid.reminder',
                            'status' => 'delivered',
                            'recipient_id' => '255712345678',
                            'pricing' => ['category' => 'utility', 'billable' => true],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $message->refresh();

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->status);
        $this->assertNotNull($message->delivered_at);
        $this->assertSame('delivered', $invitation->fresh()->send_status);
    }

    public function test_guest_list_shows_the_latest_message_status(): void
    {
        [$guest, $invitation] = $this->makeGuestAndInvitation();

        WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'reminder',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.reminder',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $this->actingAs($invitation->event->user)
            ->get(route('events.guests.index', $invitation->event))
            ->assertOk()
            ->assertSee('Delivered')
            ->assertSee('Reminder');

        $this->actingAs($invitation->event->user)
            ->get(route('events.guests.index', ['event' => $invitation->event, 'message_status' => 'delivered']))
            ->assertOk()
            ->assertSee($guest->full_name);

        $this->actingAs($invitation->event->user)
            ->get(route('events.guests.index', ['event' => $invitation->event, 'message_status' => 'failed']))
            ->assertOk()
            ->assertDontSee($guest->full_name);
    }

    public function test_reminder_status_webhook_does_not_change_invitation_state(): void
    {
        [$guest, $invitation] = $this->makeGuestAndInvitation();

        $message = WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'reminder',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.reminder-status',
            'status' => WhatsAppMessage::STATUS_ACCEPTED,
        ]);

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'statuses' => [[
                            'id' => 'wamid.reminder-status',
                            'status' => 'delivered',
                            'recipient_id' => '255712345678',
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame(WhatsAppMessage::STATUS_DELIVERED, $message->fresh()->status);

        $invitation->refresh();
        $this->assertSame('pending', $invitation->send_status);
        $this->assertNull($invitation->whatsapp_message_id);
    }

    public function test_invitation_page_shows_invitation_status_not_reminder_status(): void
    {
        [$guest, $invitation] = $this->makeGuestAndInvitation();

        WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'invitation',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.invitation-accepted',
            'status' => WhatsAppMessage::STATUS_ACCEPTED,
        ]);

        WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'reminder',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.reminder-delivered',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $invitation->update(['send_status' => 'sent', 'sent_at' => now()]);

        $this->actingAs($invitation->event->user)
            ->get(route('events.invitations.index', $invitation->event))
            ->assertOk()
            ->assertSee('Accepted')
            ->assertDontSee('Delivered');
    }

    public function test_failed_webhook_stores_the_error_details(): void
    {
        [$guest, $invitation] = $this->makeGuestAndInvitation();

        $message = WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'invitation',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.failed',
            'status' => WhatsAppMessage::STATUS_ACCEPTED,
        ]);

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'statuses' => [[
                            'id' => 'wamid.failed',
                            'status' => 'failed',
                            'recipient_id' => '255712345678',
                            'errors' => [[
                                'code' => 131047,
                                'title' => 'Message failed to send',
                                'message' => 'Re-engagement message required',
                            ]],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $message->refresh();

        $this->assertSame(WhatsAppMessage::STATUS_FAILED, $message->status);
        $this->assertSame('131047', $message->error_code);
        $this->assertSame('Re-engagement message required', $message->error_message);
        $this->assertNotNull($message->failed_at);
        $this->assertSame('failed', $invitation->fresh()->send_status);
    }

    private function postSigned(array $payload): TestResponse
    {
        $body = json_encode($payload);

        return $this->call(
            'POST',
            '/webhook/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'secret'),
            ],
            $body
        );
    }

    private function makeGuestAndInvitation(): array
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

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Jane Doe',
            'phone_number' => '0712345678',
            'amount_contributed' => 200000,
        ]);

        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ]);

        return [$guest->fresh(['event']), $invitation];
    }
}
