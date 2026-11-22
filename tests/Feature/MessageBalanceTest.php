<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventMessageCredit;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\SmsMessage;
use App\Models\User;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MessageBalanceTest extends TestCase
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
            'services.whatsapp.payment_template_name' => 'payment_notification',
            'services.whatsapp.payment_template_language' => 'sw',
            'services.nextsms.api_url' => 'https://messaging-service.co.tz/api/sms/v2/text/single',
            'services.nextsms.token' => 'test-nextsms-token',
            'services.nextsms.from' => 'HARUSI',
        ]);
    }

    public function test_new_events_start_with_no_balance_and_thank_you_disabled(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post(route('events.store'), [
            'name' => 'Fresh Wedding',
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
            'card_template' => 'classic',
            'payment_thank_you_enabled' => '1',
        ])->assertRedirect();

        $event = Event::firstOrFail();
        $this->assertFalse($event->payment_thank_you_enabled);
        $this->assertSame(0, $event->messageBalance(Event::CHANNEL_WHATSAPP));
        $this->assertSame(0, $event->messageBalance(Event::CHANNEL_SMS));
    }

    public function test_only_an_administrator_can_change_the_thank_you_setting(): void
    {
        [$user, $event] = $this->makeInvitation();
        $admin = $this->makeUser(isAdmin: true);
        $payload = [
            'name' => $event->name,
            'event_date' => $event->event_date->toDateString(),
            'location' => $event->location,
            'card_template' => 'classic',
            'payment_thank_you_enabled' => '1',
        ];

        $this->actingAs($user)->put(route('events.update', $event), $payload)->assertRedirect();
        $this->assertFalse($event->fresh()->payment_thank_you_enabled);
        $this->actingAs($user)->get(route('events.edit', $event))->assertOk()
            ->assertDontSee('name="payment_thank_you_enabled"', false);

        $this->actingAs($admin)->put(route('events.update', $event), $payload)->assertRedirect();
        $this->assertTrue($event->fresh()->payment_thank_you_enabled);

        unset($payload['payment_thank_you_enabled']);
        $this->actingAs($user)->put(route('events.update', $event), $payload)->assertRedirect();
        $this->assertTrue($event->fresh()->payment_thank_you_enabled, 'A non-admin edit must not switch it off.');
    }

    public function test_whatsapp_sending_is_blocked_when_the_balance_is_zero(): void
    {
        Http::fake();
        [$user, $event, $guest, $invitation] = $this->makeInvitation();

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('reason', 'insufficient_balance');

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'insufficient_balance');

        Http::assertNothingSent();
        $this->assertSame(0, WhatsAppMessage::count());
        $this->assertSame('pending', $invitation->fresh()->send_status);
    }

    public function test_each_accepted_whatsapp_message_takes_one_from_the_whatsapp_balance(): void
    {
        Http::fake(function () {
            static $sequence = 0;

            return Http::response(['messages' => [['id' => 'wamid.balance.'.++$sequence]]]);
        });
        [$user, $event, $guest, $invitation] = $this->makeInvitation(whatsapp: 2, sms: 5);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertOk()->assertJsonPath('success', true);
        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertOk()->assertJsonPath('success', true);

        $event->refresh();
        $this->assertSame(0, $event->whatsapp_balance);
        $this->assertSame(5, $event->sms_balance);
        $this->assertSame(
            ['invitation', 'reminder'],
            EventMessageCredit::where('type', EventMessageCredit::TYPE_USAGE)->orderBy('id')->pluck('message_type')->all()
        );

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), [
                'message_type' => 'reminder',
                'template_name' => 'reminder_sw',
            ])
            ->assertStatus(422);
        Http::assertSentCount(2);
    }

    public function test_a_rejected_whatsapp_message_does_not_use_balance(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Invalid', 'code' => 131000]], 400)]);
        [$user, $event, , $invitation] = $this->makeInvitation(whatsapp: 3);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send', [$event, $invitation]), [])
            ->assertJsonPath('success', false);

        $this->assertSame(3, $event->fresh()->whatsapp_balance);
        $this->assertSame(0, EventMessageCredit::count());
    }

    public function test_sms_uses_the_sms_balance_and_is_blocked_at_zero(): void
    {
        Http::fake(['messaging-service.co.tz/*' => Http::response(['message_id' => 'sms-1'], 200)]);
        [$user, $event, $guest, $invitation] = $this->makeInvitation(whatsapp: 4, sms: 1);

        $this->actingAs($user)
            ->postJson(route('events.guests.send-message', [$event, $guest]), ['channel' => 'sms', 'message_type' => 'reminder'])
            ->assertOk()->assertJsonPath('success', true);

        $event->refresh();
        $this->assertSame(0, $event->sms_balance);
        $this->assertSame(4, $event->whatsapp_balance);

        $this->actingAs($user)
            ->postJson(route('events.invitations.send-sms', [$event, $invitation]), [])
            ->assertStatus(422)
            ->assertJsonPath('reason', 'insufficient_balance');

        Http::assertSentCount(1);
        $this->assertSame(1, SmsMessage::count());
    }

    public function test_thank_you_message_is_skipped_without_whatsapp_balance(): void
    {
        Http::fake();
        [$user, $event, $guest] = $this->makeInvitation();
        $event->update(['payment_thank_you_enabled' => true]);

        $this->actingAs($user)->post(route('events.guests.payments.store', [$event, $guest]), [
            'amount' => 10000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ])->assertSessionHas('success')->assertSessionHas('info');

        Http::assertNothingSent();
        $this->assertSame(1, $guest->payments()->count());
    }

    public function test_only_administrators_can_adjust_the_balance(): void
    {
        [$user, $event] = $this->makeInvitation();
        $admin = $this->makeUser(isAdmin: true);

        $this->actingAs($user)
            ->post(route('events.messages.balance.store', $event), ['channel' => 'sms', 'action' => 'add', 'quantity' => 50])
            ->assertForbidden();
        $this->assertSame(0, $event->fresh()->sms_balance);

        $this->actingAs($admin)
            ->post(route('events.messages.balance.store', $event), ['channel' => 'sms', 'action' => 'add', 'quantity' => 50, 'note' => 'Paid cash'])
            ->assertRedirect(route('events.messages.report', $event));
        $this->actingAs($admin)
            ->post(route('events.messages.balance.store', $event), ['channel' => 'whatsapp', 'action' => 'add', 'quantity' => 20])
            ->assertRedirect();
        $this->actingAs($admin)
            ->post(route('events.messages.balance.store', $event), ['channel' => 'whatsapp', 'action' => 'remove', 'quantity' => 500])
            ->assertRedirect();

        $event->refresh();
        $this->assertSame(50, $event->sms_balance);
        $this->assertSame(0, $event->whatsapp_balance, 'A correction never takes the balance below zero.');
        $this->assertSame([50, 20, -20], EventMessageCredit::orderBy('id')->pluck('amount')->all());

        $this->actingAs($user)->get(route('events.messages.report', $event))
            ->assertOk()
            ->assertSee('Message balance')
            ->assertSee('Paid cash')
            ->assertDontSee('Adjust balance');
        $this->actingAs($admin)->get(route('events.messages.report', $event))->assertOk()->assertSee('Adjust balance');
    }

    public function test_buying_messages_shows_coming_soon_and_changes_nothing(): void
    {
        [$user, $event] = $this->makeInvitation();

        $this->actingAs($user)->get(route('events.messages.buy', $event))
            ->assertOk()
            ->assertSee('Buy Messages')
            ->assertSee('M-Pesa');

        $this->actingAs($user)
            ->post(route('events.messages.buy.submit', $event), [
                'channel' => 'sms', 'quantity' => 500, 'network' => 'mpesa', 'phone_number' => '0712345678',
            ])
            ->assertRedirect(route('events.messages.buy', $event))
            ->assertSessionHas('purchase_coming_soon');

        $this->actingAs($user)->withSession(['purchase_coming_soon' => true])
            ->get(route('events.messages.buy', $event))
            ->assertSee('This service is coming soon');

        $this->assertSame(0, $event->fresh()->sms_balance);
        $this->assertSame(0, EventMessageCredit::count());
    }

    private function makeUser(bool $isAdmin = false): User
    {
        return User::create([
            'name' => 'User '.uniqid(),
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => $isAdmin,
            'can_send_whatsapp' => true,
        ]);
    }

    private function makeInvitation(int $whatsapp = 0, int $sms = 0): array
    {
        $user = $this->makeUser();

        $event = Event::create([
            'user_id' => $user->id,
            'name' => 'Balance Wedding',
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
            'card_template' => 'classic',
        ]);

        if ($whatsapp > 0 || $sms > 0) {
            $this->fundEvent($event, $whatsapp, $sms);
        }

        $guest = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Balance Guest',
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
