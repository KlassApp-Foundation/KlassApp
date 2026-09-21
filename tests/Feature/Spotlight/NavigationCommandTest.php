<?php
/**
 * SPDX-License-Identifier: MIT
 *
 * Command-palette navigation (Step 4).
 *
 * The palette's destinations come from config/navigation.php, and the scoping must
 * be SERVER-SIDE — a role may only ever see (and navigate to) its own sidebar's
 * entries. These tests pin that, including refusing a crafted destination.
 */
namespace Tests\Feature\Spotlight;

use App\Models\User;
use App\Spotlight\NavigationCommand;
use LivewireUI\Spotlight\Spotlight;
use Tests\TestCase;

class NavigationCommandTest extends TestCase
{
    private function user(int $usergroup, ?int $schoolId = 1): User
    {
        $u = new User();
        $u->id = 999;
        $u->usergroup_id = $usergroup;
        $u->school_id = $schoolId;

        return $u;
    }

    public function test_role_mapping_covers_every_navigation_role(): void
    {
        $this->assertSame('admin', NavigationCommand::roleFor($this->user(3)));
        $this->assertSame('admin', NavigationCommand::roleFor($this->user(4)), 'SchoolSubadmin reuses the admin UI');
        $this->assertSame('teacher', NavigationCommand::roleFor($this->user(5)));
        $this->assertSame('student', NavigationCommand::roleFor($this->user(6)));
        $this->assertSame('parent', NavigationCommand::roleFor($this->user(7)));
        $this->assertSame('superadmin', NavigationCommand::roleFor($this->user(1, null)));

        // every mapped role must exist in the navigation config
        foreach (array_unique(array_values(NavigationCommand::ROLE_BY_USERGROUP)) as $role) {
            $this->assertNotNull(config('navigation.roles.'.$role), "navigation config is missing role [{$role}]");
        }
    }

    public function test_destinations_are_scoped_to_the_role(): void
    {
        $parent = NavigationCommand::destinationsFor($this->user(7));
        // school_id null: the class-teacher-only items stay hidden without a DB read
        $teacher = NavigationCommand::destinationsFor($this->user(5, null));

        $this->assertSame(['Dashboard', 'Children'], array_values($parent));

        // a parent must never be offered admin/teacher destinations
        $this->assertStringNotContainsString('/admin/', implode(' ', array_keys($parent)));
        $this->assertNotContains('Settings', array_values($teacher));
        $this->assertContains('Homework', array_values($teacher));
        $this->assertNotContains('Report Cards', array_values($teacher), 'class-teacher items are conditional');
        $this->assertNotContains('Class Streams', array_values($teacher), 'class-teacher items are conditional');
    }

    public function test_execute_refuses_a_destination_outside_the_role(): void
    {
        $user = $this->user(7);
        $this->actingAs($user);

        $command = new NavigationCommand();
        $own = array_keys(NavigationCommand::destinationsFor($user));
        $foreign = url('admin/reports/cards');
        $this->assertNotContains($foreign, $own);

        $refused = \Mockery::mock(Spotlight::class);
        $refused->shouldNotReceive('redirect');
        $command->execute($refused, $foreign);
        $command->execute($refused, null);

        $allowed = \Mockery::mock(Spotlight::class);
        $allowed->shouldReceive('redirect')->once()->with($own[0]);
        $command->execute($allowed, $own[0]);
    }

    public function test_search_destination_filters_by_query(): void
    {
        $this->actingAs($this->user(7));
        $command = new NavigationCommand();

        $this->assertCount(2, $command->searchDestination(''));
        $hits = $command->searchDestination('child');
        $this->assertCount(1, $hits);
        $this->assertSame('Children', $hits[0]->getName());
        $this->assertCount(0, $command->searchDestination('settings'), 'a parent has no Settings entry');
    }

    public function test_hidden_for_unmapped_roles(): void
    {
        $this->assertFalse((new NavigationCommand())->shouldBeShown(), 'guest');
        $this->actingAs($this->user(2));   // SchoolSubadmin-esque unmapped group
        $this->assertFalse((new NavigationCommand())->shouldBeShown());
    }
}
