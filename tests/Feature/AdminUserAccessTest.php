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
        $this->assertSame($admin->id, $event->fresh()->user_id);
        $this->assertTrue($event->fresh()->users()->whereKey($planner->id)->exists());

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

    public function test_editing_a_user_only_changes_that_users_shared_event_access(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $planner = $this->makeUser();
        $otherManager = $this->makeUser();

        $plannerEvent = $this->makeEvent($planner, 'Planner Wedding');
        $sharedEvent = $this->makeEvent($admin, 'Shared Wedding');
        $sharedEvent->users()->attach($otherManager->id);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $planner), [
                'name' => $planner->name,
                'email' => $planner->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [$plannerEvent->id, $sharedEvent->id],
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertSame($planner->id, $plannerEvent->fresh()->user_id);
        $this->assertSame($admin->id, $sharedEvent->fresh()->user_id);
        $this->assertTrue($sharedEvent->users()->whereKey($planner->id)->exists());
        $this->assertTrue($sharedEvent->users()->whereKey($otherManager->id)->exists());

        $this->actingAs($admin)
            ->put(route('admin.users.update', $planner), [
                'name' => $planner->name,
                'email' => $planner->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [$plannerEvent->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($sharedEvent->fresh()->users()->whereKey($planner->id)->exists());
        $this->assertTrue($sharedEvent->fresh()->users()->whereKey($otherManager->id)->exists());
        $this->assertSame($admin->id, $sharedEvent->fresh()->user_id);
    }

    public function test_assigning_a_new_manager_keeps_the_owner_and_existing_managers(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $existingManager = $this->makeUser();
        $newManager = $this->makeUser();
        $event = $this->makeEvent($owner, 'Shared Wedding');
        $event->users()->attach($existingManager->id);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $newManager), [
                'name' => $newManager->name,
                'email' => $newManager->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [$event->id],
            ])
            ->assertRedirect(route('admin.users.index'));

        $event->refresh();

        $this->assertSame($owner->id, $event->user_id);
        $this->assertTrue($event->users()->whereKey($owner->id)->exists());
        $this->assertTrue($event->users()->whereKey($existingManager->id)->exists());
        $this->assertTrue($event->users()->whereKey($newManager->id)->exists());

        foreach ([$owner, $existingManager, $newManager] as $manager) {
            $this->actingAs($manager)
                ->get(route('events.guests.index', $event))
                ->assertOk();
        }
    }

    public function test_an_event_owner_keeps_access_even_when_unchecked_in_the_access_form(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $event = $this->makeEvent($owner, 'Owner Wedding');

        $this->actingAs($admin)
            ->put(route('admin.users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'password' => '',
                'password_confirmation' => '',
                'events' => [],
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame($owner->id, $event->fresh()->user_id);
        $this->assertTrue($event->fresh()->users()->whereKey($owner->id)->exists());

        $this->actingAs($owner)
            ->get(route('events.guests.index', $event))
            ->assertOk();
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
