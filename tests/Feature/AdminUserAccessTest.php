<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_access_admin_user_management(): void
    {
        $user = $this->makeUser();
        $event = $this->makeEvent($user);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.users.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('events.guests.index', $event))
            ->assertOk();
    }

    public function test_admin_can_create_a_user_and_assign_event_access(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $event = $this->makeEvent($admin, 'Admin Wedding');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Users &amp; Event Access', false)
            ->assertSee($admin->name);

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Assigned Planner',
                'email' => 'planner@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'events' => [$event->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $planner = User::where('email', 'planner@example.com')->firstOrFail();

        $this->assertFalse($planner->isAdmin());
        $this->assertTrue(Hash::check('password123', $planner->password));
        $this->assertSame($planner->id, $event->fresh()->user_id);

        $this->actingAs($planner)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($planner)
            ->get(route('events.guests.index', $event))
            ->assertOk();
    }

    public function test_assigned_user_only_sees_their_events_while_admin_sees_all(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $planner = $this->makeUser();
        $assigned = $this->makeEvent($planner, 'Assigned Wedding');
        $other = $this->makeEvent($admin, 'Private Wedding');

        $this->actingAs($planner)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee('Assigned Wedding')
            ->assertDontSee('Private Wedding');

        $this->actingAs($planner)
            ->get(route('events.guests.index', $other))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee('Assigned Wedding')
            ->assertSee('Private Wedding');

        $this->actingAs($admin)
            ->get(route('events.guests.index', $assigned))
            ->assertOk();
    }

    public function test_editing_a_user_transfers_selected_events_and_returns_unselected_events_to_the_admin(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $planner = $this->makeUser();

        $first = $this->makeEvent($planner, 'First Wedding');
        $second = $this->makeEvent($admin, 'Second Wedding');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $planner), [
                'name' => $planner->name,
                'email' => $planner->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [$second->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame($admin->id, $first->fresh()->user_id);
        $this->assertSame($planner->id, $second->fresh()->user_id);
    }

    public function test_self_registered_users_start_with_no_assigned_events(): void
    {
        $this->post(route('register'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'new-user@example.com')->firstOrFail();

        $this->assertFalse($user->isAdmin());
        $this->assertSame(0, $user->events()->count());

        $this->actingAs($user)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee('No events');
    }

    private function makeUser(bool $isAdmin = false): User
    {
        return User::create([
            'name' => 'User '.uniqid(),
            'email' => 'user-'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => $isAdmin,
        ]);
    }

    private function makeEvent(User $user, string $name = 'Wedding'): Event
    {
        return Event::create([
            'user_id' => $user->id,
            'name' => $name,
            'slug' => str()->slug($name).'-'.uniqid(),
            'event_date' => now()->addMonth()->toDateString(),
            'location' => 'Dar es Salaam',
        ]);
    }
}
