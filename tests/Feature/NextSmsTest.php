<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NextSmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.nextsms.api_url' => 'https://messaging-service.co.tz/api/sms/v2/text/single',
            'services.nextsms.token' => 'test-nextsms-token',
            'services.nextsms.from' => 'HARUSI',
            'services.nextsms.reminder_payment_number' => '0712345678',
            'services.nextsms.reminder_payment_name' => 'Jane Doe',
        ]);
    }

    public function test_guest_list_can_send_and_persist_reminder_sms(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['message_id' => 'nextsms-123'], 200)]);
        [$user, $event, $guest] = $this->makeGuest();

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status.state', 'sent')
            ->assertJsonPath('status.message_type', 'Reminder SMS');

        $message = SmsMessage::firstOrFail();
        $this->assertSame('sent', $message->status);
        $this->assertSame('nextsms-123', $message->provider_message_id);
        $this->assertSame('255712345678', $message->to_phone);
        $this->assertStringContainsString('TZS 200,000', $message->text);
        $this->assertStringContainsString('kwa ajili ya Wedding', $message->text);
        $this->assertStringContainsString('Jane Doe', $message->text);
        $this->assertNotNull($message->sent_at);

        Http::assertSent(fn ($request) => $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
            && $request->hasHeader('Authorization', 'Bearer test-nextsms-token')
            && $request['from'] === 'HARUSI'
            && $request['to'] === '255712345678'
            && $request['reference'] === $message->reference
            && $request['flash'] === 0);
    }

    public function test_all_event_payment_accounts_are_included_in_the_reminder_sms(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['status' => 'accepted'], 200)]);
        config([
            'services.nextsms.reminder_payment_number' => '00000000',
            'services.nextsms.reminder_payment_name' => 'Legacy Global Account',
        ]);
        [$user, $event, $guest] = $this->makeGuest();

        $this->actingAs($user)
            ->post(route('events.payment-methods.store', $event), [
                'payment_name' => 'M-Pesa',
                'payment_number' => '0755000111',
            ])
            ->assertRedirect(route('events.show', $event));

        $this->actingAs($user)
            ->post(route('events.payment-methods.store', $event), [
                'payment_name' => 'CRDB',
                'payment_number' => '0155000222',
            ])
            ->assertRedirect(route('events.show', $event));

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('M-Pesa')
            ->assertSee('0755000111')
            ->assertSee('CRDB')
            ->assertSee('0155000222');

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertOk();

        $message = SmsMessage::where('message_type', 'reminder')->latest('id')->firstOrFail();
        $this->assertStringContainsString('M-Pesa: 0755000111; CRDB: 0155000222', $message->text);
        $this->assertStringNotContainsString('Legacy Global Account', $message->text);
        $this->assertStringNotContainsString('00000000', $message->text);
    }

    public function test_payment_account_removal_is_scoped_to_its_event(): void
    {
        [$user, $event] = $this->makeGuest();
        $paymentMethod = $event->paymentMethods()->create([
            'payment_name' => 'M-Pesa',
            'payment_number' => '0755000111',
        ]);
        $otherEvent = Event::create([
            'user_id' => $user->id,
            'name' => 'Other Event',
            'event_date' => '2026-12-11',
            'location' => 'Kariakoo',
        ]);
        $otherPaymentMethod = $otherEvent->paymentMethods()->create([
            'payment_name' => 'CRDB',
            'payment_number' => '0155000222',
        ]);

        $this->actingAs($user)
            ->delete(route('events.payment-methods.destroy', [$event, $paymentMethod]))
            ->assertRedirect(route('events.show', $event));

        $this->assertDatabaseMissing('event_payment_methods', ['id' => $paymentMethod->id]);

        $this->actingAs($user)
            ->delete(route('events.payment-methods.destroy', [$event, $otherPaymentMethod]))
            ->assertNotFound();

        $this->assertDatabaseHas('event_payment_methods', ['id' => $otherPaymentMethod->id]);
    }

    public function test_invitation_queue_can_send_sms_with_the_public_invitation_link(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['status' => 'accepted'], 200)]);
        [$user, $event, $guest, $invitation] = $this->makeGuest();

        $this->actingAs($user)
            ->post(route('events.invitations.send-sms', [$event, $invitation]))
            ->assertRedirect();

        $message = SmsMessage::where('message_type', 'invitation')->firstOrFail();
        $this->assertSame($invitation->id, $message->invitation_id);
        $this->assertStringContainsString($event->name, $message->text);
        $this->assertStringContainsString($guest->unique_id, $message->text);
        $this->assertStringContainsString(route('invitation.public', $invitation->qr_code_data), $message->text);
        $this->assertSame('sent', $message->status);
    }

    public function test_provider_rejection_is_persisted_as_failed(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['message' => 'Invalid sender'], 422)]);
        [$user, $event, $guest] = $this->makeGuest();

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status.state', 'failed');

        $message = SmsMessage::firstOrFail();
        $this->assertSame('failed', $message->status);
        $this->assertSame('Invalid sender', $message->error_message);
    }

    public function test_sms_sending_requires_the_existing_message_permission(): void
    {
        Http::fake();
        [$user, $event, $guest, $invitation] = $this->makeGuest();
        $user->update(['can_send_whatsapp' => false]);

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'channel' => 'sms',
                'message_type' => 'reminder',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('events.invitations.send-sms', [$event, $invitation]))
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_latest_sms_status_is_visible_on_guest_and_invitation_lists(): void
    {
        [$user, $event, $guest, $invitation] = $this->makeGuest();
        SmsMessage::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'invitation_id' => $invitation->id,
            'message_type' => 'invitation',
            'to_phone' => $guest->formatted_phone,
            'from_name' => 'HARUSI',
            'text' => 'Invitation text',
            'reference' => 'sms-test-reference',
            'status' => 'sent',
            'status_updated_at' => now(),
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk()
            ->assertSee('SMS Sent');

        $this->actingAs($user)
            ->get(route('events.invitations.index', $event))
            ->assertOk()
            ->assertSee('SMS Sent')
            ->assertSee('Send invitation SMS');
    }

    private function makeGuest(): array
    {
        $user = User::factory()->create(['can_send_whatsapp' => true]);
        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Wedding',
            'event_date' => '2026-12-10',
            'event_time' => '17:00',
            'location' => 'Kariakoo',
            'hall' => 'City Hall',
        ]);
        $guest = Guest::create([
            'event_id' => $event->id,
            'unique_id' => 'G1-ABC12345',
            'full_name' => 'Jane Doe',
            'phone_number' => '0712345678',
            'amount_contributed' => 200000,
            'paid_amount' => 200000,
        ]);
        $invitation = Invitation::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'qr_code_data' => $guest->unique_id,
        ]);

        return [$user, $event, $guest, $invitation];
    }
}