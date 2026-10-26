<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBudgetItem;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_page_shows_services_vendor_details_and_funding_totals(): void
    {
        [$user, $event] = $this->makeEvent();

        EventBudgetItem::create([
            'event_id' => $event->id,
            'category' => 'Venue',
            'service_name' => 'Wedding Hall',
            'vendor_name' => 'Serena Hotel',
            'vendor_contact_name' => 'Mr. John',
            'vendor_phone' => '0712345678',
            'vendor_email' => 'john@example.com',
            'total_price' => 1000000,
            'paid_amount' => 250000,
            'due_date' => now()->addDays(10)->toDateString(),
            'notes' => 'Includes chairs and tables',
        ]);

        EventBudgetItem::create([
            'event_id' => $event->id,
            'category' => 'Catering',
            'service_name' => 'Dinner Buffet',
            'vendor_name' => 'Mama Asha Catering',
            'total_price' => 500000,
            'paid_amount' => 500000,
        ]);

        Guest::create([
            'event_id' => $event->id,
            'full_name' => 'Jane Doe',
            'phone_number' => '0712345678',
            'amount_contributed' => 900000,
            'paid_amount' => 400000,
        ]);

        $this->actingAs($user)
            ->get(route('events.budget.index', $event))
            ->assertOk()
            ->assertSee('Budget & Vendors')
            ->assertSee('Wedding Hall')
            ->assertSee('Serena Hotel')
            ->assertSee('Mama Asha Catering')
            ->assertSee('Includes chairs and tables')
            ->assertSee('Funding position')
            ->assertSee('1,500,000')
            ->assertSee('750,000')
            ->assertSee('750,000')
            ->assertSee(route('events.budget.report', $event))
            ->assertViewHas('budget', function (array $budget) {
                return $budget['itemCount'] === 2
                    && $budget['vendorCount'] === 2
                    && $budget['totalBudget'] === 1500000.0
                    && $budget['totalPaid'] === 750000.0
                    && $budget['outstanding'] === 750000.0
                    && $budget['paidRate'] === 50
                    && $budget['statusCounts']['paid'] === 1
                    && $budget['statusCounts']['partial'] === 1;
            })
            ->assertViewHas('projectedBalance', -600000.0)
            ->assertViewHas('fundingCoverage', 60);
    }

    public function test_budget_services_can_be_created_updated_and_removed(): void
    {
        [$user, $event] = $this->makeEvent();

        $this->actingAs($user)
            ->post(route('events.budget.store', $event), [
                'category' => 'Photography',
                'service_name' => 'Wedding Photos',
                'vendor_name' => 'Lens Studio',
                'vendor_contact_name' => 'Asha',
                'vendor_phone' => '0713000111',
                'vendor_email' => 'asha@example.com',
                'total_price' => 800000,
                'paid_amount' => 300000,
                'due_date' => now()->addDays(20)->toDateString(),
                'notes' => 'Full day coverage',
            ])
            ->assertRedirect(route('events.budget.index', $event))
            ->assertSessionHas('success');

        $item = EventBudgetItem::firstOrFail();

        $this->assertSame('Wedding Photos', $item->service_name);
        $this->assertSame('partial', $item->status);
        $this->assertEquals(500000, $item->balance);
        $this->assertDatabaseHas('activity_logs', [
            'event_id' => $event->id,
            'action' => 'budget_item_added',
        ]);

        $this->actingAs($user)
            ->put(route('events.budget.update', [$event, $item]), [
                'category' => 'Photography',
                'service_name' => 'Wedding Photos & Video',
                'vendor_name' => 'Lens Studio',
                'total_price' => 800000,
                'paid_amount' => 800000,
            ])
            ->assertRedirect(route('events.budget.index', $event));

        $item->refresh();
        $this->assertSame('Wedding Photos & Video', $item->service_name);
        $this->assertSame('paid', $item->status);
        $this->assertEquals(0, $item->balance);

        $this->actingAs($user)
            ->delete(route('events.budget.destroy', [$event, $item]))
            ->assertRedirect(route('events.budget.index', $event));

        $this->assertDatabaseMissing('event_budget_items', ['id' => $item->id]);
        $this->assertDatabaseHas('activity_logs', [
            'event_id' => $event->id,
            'action' => 'budget_item_removed',
        ]);
    }

    public function test_estimated_guests_drive_cost_per_guest_and_budget_report(): void
    {
        [$user, $event] = $this->makeEvent();

        EventBudgetItem::create([
            'event_id' => $event->id,
            'category' => 'Catering',
            'service_name' => 'Dinner Buffet',
            'total_price' => 2000000,
            'paid_amount' => 500000,
        ]);

        $this->actingAs($user)
            ->put(route('events.budget.estimated-guests', $event), ['estimated_guests' => 250])
            ->assertRedirect(route('events.budget.index', $event))
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertSame(250, $event->estimated_guests);
        $this->assertDatabaseHas('activity_logs', [
            'event_id' => $event->id,
            'action' => 'budget_guests_updated',
        ]);

        $this->actingAs($user)
            ->get(route('events.budget.index', $event))
            ->assertOk()
            ->assertSee('8,000')
            ->assertViewHas('budget', fn (array $budget) => $budget['estimatedGuests'] === 250
                && $budget['costPerGuest'] === 8000.0
                && $budget['paidPerGuest'] === 2000.0);

        $this->actingAs($user)
            ->get(route('events.budget.report', $event))
            ->assertOk()
            ->assertSee('Budget Report')
            ->assertSee('Funding bridge')
            ->assertSee('Budget allocation by category')
            ->assertSee('Vendor obligations')
            ->assertSee('Guest economics')
            ->assertSee('Dinner Buffet')
            ->assertSee('Budget / guest')
            ->assertViewHas('budget', fn (array $budget) => $budget['totalBudget'] === 2000000.0
                && $budget['outstanding'] === 1500000.0
                && $budget['paidRate'] === 25);
    }

    public function test_budget_pages_and_mutations_are_forbidden_for_another_user(): void
    {
        [, $event] = $this->makeEvent();

        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->actingAs($intruder)
            ->get(route('events.budget.index', $event))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->get(route('events.budget.report', $event))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('events.budget.store', $event), [
                'service_name' => 'Hack Hall',
                'total_price' => 1,
            ])
            ->assertForbidden();
    }

    public function test_a_budget_item_cannot_be_changed_through_another_event(): void
    {
        [$user, $event] = $this->makeEvent();
        [, $otherEvent] = $this->makeEvent($user, 'Other Wedding');

        $item = EventBudgetItem::create([
            'event_id' => $event->id,
            'service_name' => 'Hall',
            'total_price' => 100000,
        ]);

        $this->actingAs($user)
            ->put(route('events.budget.update', [$otherEvent, $item]), [
                'service_name' => 'Changed Hall',
                'total_price' => 200000,
            ])
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('events.budget.destroy', [$otherEvent, $item]))
            ->assertNotFound();

        $this->assertDatabaseHas('event_budget_items', [
            'id' => $item->id,
            'service_name' => 'Hall',
        ]);
    }

    public function test_event_pages_link_to_budget_and_show_budget_position(): void
    {
        [$user, $event] = $this->makeEvent();

        EventBudgetItem::create([
            'event_id' => $event->id,
            'category' => 'Venue',
            'service_name' => 'Wedding Hall',
            'vendor_name' => 'Serena Hotel',
            'total_price' => 1200000,
            'paid_amount' => 400000,
        ]);

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee(route('events.budget.index', $event))
            ->assertSee('Budget &amp; vendors', false)
            ->assertSee('1.2M')
            ->assertSee('800K');

        $this->actingAs($user)
            ->get(route('events.report', $event))
            ->assertOk()
            ->assertSee(route('events.budget.report', $event))
            ->assertSee('Budget &amp; vendor position', false)
            ->assertSee('1,200,000')
            ->assertSee('800,000')
            ->assertViewHas('budgetPosition', fn (array $position) => $position['totalBudget'] === 1200000.0
                && $position['vendorPaid'] === 400000.0
                && $position['vendorOutstanding'] === 800000.0);
    }

    private function makeEvent(?User $user = null, string $name = 'Wedding'): array
    {
        $user ??= User::create([
            'name' => 'Host',
            'email' => 'host-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
        ]);

        $event = Event::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => str()->slug($name).'-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);

        return [$user, $event];
    }
}
