<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('Admin One', 'adminone', 'admin');
        $this->staff = $this->makeUser('Staff One', 'staffone', 'staff');
    }

    private function makeUser(string $name, string $username, string $role, bool $active = true): User
    {
        return User::create(['name' => $name, 'username' => $username, 'email' => $username.'@t.test', 'password' => 'secret123', 'role' => $role, 'is_active' => $active]);
    }

    private function newUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Maria Clara', 'username' => 'maria', 'email' => 'maria@t.test', 'role' => 'staff',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ], $overrides);
    }

    // ---- Access control ----

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
        $this->get(route('activity-logs.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_use_user_management_or_activity_logs(): void
    {
        $this->actingAs($this->staff)->get(route('users.index'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('users.show', $this->admin))->assertForbidden();
        $this->actingAs($this->staff)->post(route('users.store'), $this->newUserData(['role' => 'admin']))->assertForbidden();
        $this->actingAs($this->staff)->put(route('users.update', $this->staff), ['name' => 'X', 'username' => 'x1', 'email' => 'x@t.test', 'role' => 'admin'])->assertForbidden();
        $this->actingAs($this->staff)->patch(route('users.status', $this->admin))->assertForbidden();
        $this->actingAs($this->staff)->put(route('users.password', $this->admin), ['password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertForbidden();
        $this->actingAs($this->staff)->get(route('activity-logs.index'))->assertForbidden();

        $this->assertSame('staff', $this->staff->fresh()->role);
        $this->assertDatabaseMissing('users', ['username' => 'maria']);
    }

    // ---- Users ----

    public function test_admin_sees_the_user_list_and_can_search_it(): void
    {
        $this->actingAs($this->admin)->get(route('users.index'))->assertOk()->assertSee('Staff One')->assertSee('Admin One');

        $this->actingAs($this->admin)->get(route('users.index', ['search' => 'staffone']))
            ->assertOk()->assertSee('<td>staffone@t.test</td>', false)
            // The header dropdown always shows the signed-in admin's email, so check the table cell only.
            ->assertDontSee('<td>adminone@t.test</td>', false);
    }

    public function test_admin_can_create_a_staff_account_with_a_hashed_password(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData())->assertRedirect(route('users.index'));

        $user = User::where('username', 'maria')->first();
        $this->assertNotNull($user);
        $this->assertSame('staff', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotSame('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('activity_logs', ['module' => 'Users', 'action' => 'create', 'user_id' => $this->admin->id]);
    }

    public function test_username_and_email_must_be_unique_and_password_confirmed(): void
    {
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData(['username' => 'staffone']))->assertSessionHasErrors('username');
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData(['email' => 'staffone@t.test']))->assertSessionHasErrors('email');
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData(['password_confirmation' => 'different1']))->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->actingAs($this->admin)->post(route('users.store'), $this->newUserData(['role' => 'owner']))->assertSessionHasErrors('role');

        $this->assertSame(2, User::count());
    }

    public function test_admin_can_edit_another_user_and_assign_a_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->staff), [
            'name' => 'Staff Renamed', 'username' => 'staffone', 'email' => 'staffone@t.test', 'role' => 'admin',
        ])->assertRedirect(route('users.index'));

        $this->staff->refresh();
        $this->assertSame('Staff Renamed', $this->staff->name);
        $this->assertSame('admin', $this->staff->role);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Users', 'action' => 'update']);
    }

    public function test_editing_never_changes_the_password(): void
    {
        $hash = $this->staff->password;

        $this->actingAs($this->admin)->put(route('users.update', $this->staff), [
            'name' => 'Staff One', 'username' => 'staffone', 'email' => 'staffone@t.test', 'role' => 'staff', 'password' => 'hacked12345',
        ]);

        $this->assertSame($hash, $this->staff->fresh()->password);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'name' => 'Admin One', 'username' => 'adminone', 'email' => 'adminone@t.test', 'role' => 'staff',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_can_deactivate_and_reactivate_a_user(): void
    {
        $this->actingAs($this->admin)->patch(route('users.status', $this->staff))->assertRedirect();
        $this->assertFalse($this->staff->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Users', 'action' => 'deactivate']);

        $this->actingAs($this->admin)->patch(route('users.status', $this->staff))->assertRedirect();
        $this->assertTrue($this->staff->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Users', 'action' => 'activate']);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $this->actingAs($this->admin)->patch(route('users.status', $this->admin))->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_deactivated_users_cannot_log_in_or_keep_using_the_system(): void
    {
        $this->staff->update(['is_active' => false]);

        $this->post(route('login.attempt'), ['username' => 'staffone', 'password' => 'secret123'])->assertSessionHasErrors('username');
        $this->assertGuest();

        // A session that was already open is signed out on the next request.
        $this->actingAs($this->staff)->get(route('sales.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_reset_a_password(): void
    {
        $this->actingAs($this->admin)->put(route('users.password', $this->staff), [
            'password' => 'brandnew123', 'password_confirmation' => 'brandnew123',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('brandnew123', $this->staff->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['module' => 'Users', 'action' => 'reset_password']);
    }

    public function test_reset_password_needs_a_confirmed_password_of_eight_characters(): void
    {
        $this->actingAs($this->admin)->put(route('users.password', $this->staff), ['password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrorsIn('reset', 'password');

        $this->assertTrue(Hash::check('secret123', $this->staff->fresh()->password));
    }

    public function test_user_details_page_loads(): void
    {
        $this->actingAs($this->admin)->get(route('users.show', $this->staff))->assertOk()->assertSee('Staff One')->assertSee('staffone');
    }

    // ---- Activity logs ----

    public function test_activity_logs_list_newest_first_and_can_be_filtered(): void
    {
        ActivityLog::record('Inventory', 'create', 'Added product Blue Shirt.', $this->staff);
        ActivityLog::record('Sales', 'sale', 'Sale OSAM-1 recorded.', $this->admin);

        $this->actingAs($this->admin)->get(route('activity-logs.index'))
            ->assertOk()->assertSeeInOrder(['Sale OSAM-1 recorded.', 'Added product Blue Shirt.']);

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['module' => 'Sales']))
            ->assertOk()->assertSee('Sale OSAM-1 recorded.')->assertDontSee('Added product Blue Shirt.');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['user' => $this->staff->id]))
            ->assertOk()->assertSee('Added product Blue Shirt.')->assertDontSee('Sale OSAM-1 recorded.');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['action' => 'create']))
            ->assertOk()->assertSee('Added product Blue Shirt.')->assertDontSee('Sale OSAM-1 recorded.');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['search' => 'Blue Shirt']))
            ->assertOk()->assertSee('Added product Blue Shirt.')->assertDontSee('Sale OSAM-1 recorded.');

        $this->actingAs($this->admin)->get(route('activity-logs.index', ['date_from' => '2020-01-01', 'date_to' => '2020-01-02']))
            ->assertOk()->assertSee('No activities found');
    }

    public function test_activity_logs_cannot_be_edited_or_deleted(): void
    {
        $log = ActivityLog::record('Sales', 'sale', 'Original text.', $this->admin);

        $log->description = 'Changed';
        $log->save();
        $log->delete();

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id, 'description' => 'Original text.']);

        // There are no edit or delete routes at all.
        $this->actingAs($this->admin)->delete('/activity-logs/'.$log->id)->assertNotFound();
        $this->actingAs($this->admin)->put('/activity-logs/'.$log->id)->assertNotFound();
    }

    public function test_login_and_logout_are_logged(): void
    {
        $this->post(route('login.attempt'), ['username' => 'adminone', 'password' => 'secret123']);
        $this->assertDatabaseHas('activity_logs', ['module' => 'Authentication', 'action' => 'login', 'user_name' => 'Admin One']);

        $this->post(route('logout'));
        $this->assertDatabaseHas('activity_logs', ['module' => 'Authentication', 'action' => 'logout', 'user_name' => 'Admin One']);
    }
}
