<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use App\Models\WebhookLog;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventMessageReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_report_shows_delivery_channels_replies_and_specific_reply_text(): void
    {
        [$user, $event, $guest] = $this->makeEventWithGuest();

        WhatsAppMessage::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'message_type' => 'invitation',
            'to_phone' => $guest->formatted_phone,
            'template_name' => 'wedding_invitation',
            'whatsapp_message_id' => 'wamid.delivered',
            'status' => WhatsAppMessage::STATUS_DELIVERED,
        ]);

        WhatsAppMessage::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'message_type' => 'reminder',
            'to_phone' => $guest->formatted_phone,
            'template_name' => 'reminder_sw',
            'whatsapp_message_id' => 'wamid.read',
            'status' => WhatsAppMessage::STATUS_READ,
        ]);

        WhatsAppMessage::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'message_type' => 'payment_thank_you',
            'to_phone' => $guest->formatted_phone,
            'template_name' => 'payment_notification',
            'whatsapp_message_id' => 'wamid.failed',
            'status' => WhatsAppMessage::STATUS_FAILED,
        ]);

        WebhookLog::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'direction' => 'incoming',
            'method' => 'POST',
            'event_type' => 'message_reply',
            'source_phone' => $guest->formatted_phone,
            'summary' => 'Text: Yes I will come',
            'parsed_data' => ['type' => 'text', 'text' => ['body' => 'Yes I will come']],
            'status_code' => '200',
        ]);

        $this->actingAs($user)
            ->get(route('events.messages.report', $event))
            ->assertOk()
            ->assertSee('Message Tracking Report')
            ->assertSee('WhatsApp')
            ->assertSee('Normal messages')
            ->assertSee('Coming soon')
            ->assertSee('Invitations')
            ->assertSee('Reminders')
            ->assertSee('Payment thank-you')
            ->assertSee('Jane Doe')
            ->assertSee('Yes I will come')
            ->assertViewHas('stats', function (array $stats) {
                return $stats['totalMessages'] === 3
                    && $stats['delivered'] === 2
                    && $stats['read'] === 1
                    && $stats['failed'] === 1
                    && $stats['replyCount'] === 1
                    && $stats['uniqueRepliers'] === 1;
            })
            ->assertViewHas('channels', function (array $channels) {
                return $channels['whatsapp']['total'] === 3
                    && $channels['normal']['total'] === 0
                    && $channels['normal']['available'] === false;
            });
    }

    public function test_message_report_is_forbidden_for_another_events_owner(): void
    {
        [, $event] = $this->makeEventWithGuest();

        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($intruder)
            ->get(route('events.messages.report', $event))
            ->assertForbidden();
    }

    public function test_event_pages_link_to_the_message_report(): void
    {
        [$user, $event] = $this->makeEventWithGuest();

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(route('events.messages.report', $event));

        $this->actingAs($user)
            ->get(route('events.report', $event))
            ->assertOk()
            ->assertSee(route('events.messages.report', $event));
    }

    private function makeEventWithGuest(): array
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
            'amount_contributed' => 100000,
        ]);

        return [$user, $event, $guest];
    }
}
