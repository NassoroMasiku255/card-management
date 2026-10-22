<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_partial_payment_updates_the_guest_and_creates_history(): void
    {
        [$user, $event, $guest] = $this->eventWithGuest(100000);

        $this->actingAs($user)
            ->post(route('events.guests.payments.store', [$event, $guest]), [
                'amount' => 40000,
                'method' => 'mobile_money',
                'reference' => 'MPESA-123',
                'paid_at' => now()->toDateString(),
                'notes' => 'First instalment',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $guest->refresh();

        $this->assertEquals(40000, (float) $guest->paid_amount);
        $this->assertEquals(60000, $guest->balance);
        $this->assertSame('partial', $guest->payment_status);
        $this->assertDatabaseHas('guest_payments', [
            'guest_id' => $guest->id,
            'event_id' => $event->id,
            'amount' => 40000,
            'method' => 'mobile_money',
            'reference' => 'MPESA-123',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event_id' => $event->id,
            'action' => 'payment_recorded',
        ]);
    }

    public function test_paying_the_full_pledged_amount_marks_the_guest_as_paid(): void
    {
        [$user, $event, $guest] = $this->eventWithGuest(100000);

        $this->actingAs($user)->post(route('events.guests.payments.store', [$event, $guest]), [
            'amount' => 100000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $guest->refresh();

        $this->assertEquals(100000, (float) $guest->paid_amount);
        $this->assertEquals(0, $guest->balance);
        $this->assertSame('paid', $guest->payment_status);
    }

    public function test_deleting_a_payment_recalculates_the_guest_balance(): void
    {
        [$user, $event, $guest] = $this->eventWithGuest(100000);
        $payment = GuestPayment::create([
            'event_id' => $event->id,
            'guest_id' => $guest->id,
            'user_id' => $user->id,
            'amount' => 25000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ]);
        $guest->update(['paid_amount' => 25000]);

        $this->actingAs($user)
            ->delete(route('events.guests.payments.destroy', [$event, $guest, $payment]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('guest_payments', ['id' => $payment->id]);
        $this->assertEquals(0, (float) $guest->fresh()->paid_amount);
        $this->assertSame('unpaid', $guest->fresh()->payment_status);
    }

    public function test_payment_report_shows_totals_and_attendance_estimate(): void
    {
        [$user, $event, $single] = $this->eventWithGuest(100000, 'single');
        $double = Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Couple Guest',
            'phone_number' => '0767654321',
            'amount_contributed' => 200000,
            'paid_amount' => 200000,
            'card_type' => 'double',
        ]);

        $this->actingAs($user)
            ->get(route('events.report', $event))
            ->assertOk()
            ->assertSee('Payment &amp; Attendance Report', false)
            ->assertSee('Total Pledged')
            ->assertSee('300,000')
            ->assertViewHas('stats', function (array $stats) {
                return $stats['totalPledged'] === 300000.0
                    && $stats['totalPaid'] === 200000.0
                    && $stats['outstanding'] === 100000.0
                    && $stats['singles'] === 1
                    && $stats['doubles'] === 1
                    && $stats['estimatedAttendance'] === 3;
            });
    }

    private function eventWithGuest(float $pledged, string $cardType = 'single'): array
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
            'amount_contributed' => $pledged,
            'card_type' => $cardType,
        ]);

        return [$user, $event, $guest];
    }
}
