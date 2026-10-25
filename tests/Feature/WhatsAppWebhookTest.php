<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WebhookLog;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_TOKEN = 'test-verify-token';

    private const APP_SECRET = 'test-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.webhook_verify_token' => self::VERIFY_TOKEN,
            'services.whatsapp.app_secret' => self::APP_SECRET,
        ]);
    }

    public function test_get_handshake_echoes_the_challenge(): void
    {
        $this->get('/webhook/whatsapp?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => self::VERIFY_TOKEN,
            'hub.challenge' => '1234567890',
        ]))->assertOk()->assertSee('1234567890');
    }

    public function test_get_handshake_rejects_a_wrong_token(): void
    {
        $this->get('/webhook/whatsapp?'.http_build_query([
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong',
            'hub.challenge' => '1234567890',
        ]))->assertForbidden();
    }

    public function test_post_without_a_valid_signature_is_rejected(): void
    {
        $this->postJson('/webhook/whatsapp', ['object' => 'whatsapp_business_account'])
            ->assertForbidden();
    }

    public function test_button_reply_records_the_rsvp(): void
    {
        $invitation = $this->makeInvitation();

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => '255712345678',
                            'id' => 'wamid.reply',
                            'type' => 'interactive',
                            'interactive' => [
                                'type' => 'button_reply',
                                'button_reply' => [
                                    'id' => 'rsvp_yes_'.$invitation->id,
                                    'title' => 'Yes',
                                ],
                            ],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame('attending', $invitation->fresh()->rsvp_status);
        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => $invitation->event_id,
            'guest_id' => $invitation->guest_id,
            'event_type' => 'interactive_reply',
        ]);
    }

    public function test_text_reply_is_linked_to_the_event_and_guest(): void
    {
        $invitation = $this->makeInvitation();

        WhatsAppMessage::create([
            'event_id' => $invitation->event_id,
            'guest_id' => $invitation->guest_id,
            'message_type' => 'invitation',
            'to_phone' => '255712345678',
            'whatsapp_message_id' => 'wamid.outbound',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'messages' => [[
                            'from' => '255712345678',
                            'id' => 'wamid.text-reply',
                            'type' => 'text',
                            'text' => ['body' => 'Ndiyo nitakuja'],
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $log = WebhookLog::where('event_type', 'message_reply')->firstOrFail();

        $this->assertSame($invitation->event_id, $log->event_id);
        $this->assertSame($invitation->guest_id, $log->guest_id);
        $this->assertSame('Ndiyo nitakuja', $log->reply_text);
    }

    public function test_status_callback_updates_the_send_status(): void
    {
        $invitation = $this->makeInvitation(['whatsapp_message_id' => 'wamid.sent']);

        $this->postSigned([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '123',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'messaging_product' => 'whatsapp',
                        'statuses' => [[
                            'id' => 'wamid.sent',
                            'status' => 'delivered',
                            'recipient_id' => '255712345678',
                        ]],
                    ],
                ]],
            ]],
        ])->assertOk();

        $this->assertSame('delivered', $invitation->fresh()->send_status);
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
                'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, self::APP_SECRET),
            ],
            $body
        );
    }

    private function makeInvitation(array $attributes = []): Invitation
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
        ]);

        return Invitation::create(array_merge([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ], $attributes));
    }
}
