<?php

namespace Tests\Feature;

use App\Livewire\Admin\ChangePasswordModal;
use App\Livewire\Admin\StaffManager;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

function makeManagerUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Manager User',
        'email' => 'manager-'.uniqid().'@isaleeeko.ca',
        'password' => Hash::make('password123'),
        'is_admin' => true,
        'role' => User::ROLE_MANAGER,
        'is_active' => true,
        'email_verified_at' => now(),
    ], $attributes));
}

function makeAssociateUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Associate User',
        'email' => 'associate-'.uniqid().'@isaleeeko.ca',
        'password' => Hash::make('password123'),
        'is_admin' => true,
        'role' => User::ROLE_ASSOCIATE,
        'is_active' => true,
        'email_verified_at' => now(),
    ], $attributes));
}

test('guest is redirected when trying to access staff management', function () {
    $this->get(route('admin.staff'))
        ->assertRedirect(route('admin.login'));
});

test('customer is forbidden from accessing staff management', function () {
    $customer = User::factory()->create([
        'is_admin' => false,
        'role' => User::ROLE_CUSTOMER,
    ]);

    $this->actingAs($customer)
        ->get(route('admin.staff'))
        ->assertStatus(403);
});

test('store associate is forbidden from accessing staff management', function () {
    $associate = makeAssociateUser();

    $this->actingAs($associate)
        ->get(route('admin.staff'))
        ->assertStatus(403);
});

test('store manager can view staff management portal', function () {
    $manager = makeManagerUser(['name' => 'Chief Funke']);
    $associate = makeAssociateUser(['name' => 'Tunde Kitchen']);

    $this->actingAs($manager)
        ->get(route('admin.staff'))
        ->assertStatus(200)
        ->assertSee('Staff & Access Management')
        ->assertSee('Chief Funke')
        ->assertSee('Tunde Kitchen');
});

test('manager can create a new staff associate with temporary password', function () {
    $manager = makeManagerUser();
    $this->actingAs($manager);

    $email = 'newbie-'.uniqid().'@isaleeeko.ca';

    Livewire::test(StaffManager::class)
        ->call('openCreateModal')
        ->assertSet('showCreateModal', true)
        ->set('create_name', 'Fola Adebayo')
        ->set('create_email', $email)
        ->set('create_phone', '+1 (506) 555-0100')
        ->set('create_role', 'associate')
        ->set('create_password', 'tempSecret123!')
        ->call('createStaff')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Fola Adebayo');
    expect($user->role)->toBe('associate');
    expect($user->is_admin)->toBeTrue();
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('tempSecret123!', $user->password))->toBeTrue();
});

test('manager can edit staff details and reset password', function () {
    $manager = makeManagerUser();
    $associate = makeAssociateUser(['name' => 'Old Name']);
    $this->actingAs($manager);

    Livewire::test(StaffManager::class)
        ->call('openEditModal', $associate->id)
        ->assertSet('showEditModal', true)
        ->assertSet('edit_name', 'Old Name')
        ->set('edit_name', 'Updated Name')
        ->set('edit_password', 'newBrandPassword!')
        ->call('updateStaff')
        ->assertHasNoErrors()
        ->assertSet('showEditModal', false);

    $associate->refresh();
    expect($associate->name)->toBe('Updated Name');
    expect(Hash::check('newBrandPassword!', $associate->password))->toBeTrue();
});

test('manager can deactivate and reactivate an associate', function () {
    $manager = makeManagerUser();
    $associate = makeAssociateUser(['is_active' => true]);
    $this->actingAs($manager);

    Livewire::test(StaffManager::class)
        ->call('toggleStatus', $associate->id);

    $associate->refresh();
    expect($associate->is_active)->toBeFalse();

    Livewire::test(StaffManager::class)
        ->call('toggleStatus', $associate->id);

    $associate->refresh();
    expect($associate->is_active)->toBeTrue();
});

test('manager cannot deactivate their own active session account', function () {
    $manager = makeManagerUser();
    $this->actingAs($manager);

    Livewire::test(StaffManager::class)
        ->call('toggleStatus', $manager->id)
        ->assertSee('Security guard: You cannot deactivate your own active session account.');

    $manager->refresh();
    expect($manager->is_active)->toBeTrue();
});

test('manager cannot delete their own account', function () {
    $manager = makeManagerUser();
    $this->actingAs($manager);

    Livewire::test(StaffManager::class)
        ->call('openDeleteModal', $manager->id)
        ->assertSee('Security guard: You cannot delete your own account.')
        ->assertSet('showDeleteModal', false);
});

test('manager can delete a staff associate who has no orders', function () {
    $manager = makeManagerUser();
    $associate = makeAssociateUser();
    $this->actingAs($manager);

    Livewire::test(StaffManager::class)
        ->call('openDeleteModal', $associate->id)
        ->assertSet('showDeleteModal', true)
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', false);

    expect(User::find($associate->id))->toBeNull();
});

test('deleting a staff associate with order history revokes admin rights without hard delete', function () {
    $manager = makeManagerUser();
    $associate = makeAssociateUser();
    $this->actingAs($manager);

    Order::create([
        'user_id' => $associate->id,
        'number' => 'ORD-STAFF-'.uniqid(),
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => 'Associate Buyer',
        'contact_email' => 'buyer@isaleeeko.ca',
        'subtotal_cents' => 1000,
        'total_cents' => 1000,
        'payment_method' => 'card',
        'placed_at' => now(),
    ]);

    Livewire::test(StaffManager::class)
        ->call('openDeleteModal', $associate->id)
        ->call('confirmDelete');

    $associate->refresh();
    expect($associate)->not->toBeNull();
    expect($associate->is_admin)->toBeFalse();
    expect($associate->is_active)->toBeFalse();
});

test('staff member can change their own password via ChangePasswordModal', function () {
    $associate = makeAssociateUser(['password' => Hash::make('originalPassword123')]);
    $this->actingAs($associate);

    Livewire::test(ChangePasswordModal::class)
        ->call('open')
        ->assertSet('isOpen', true)
        ->set('current_password', 'originalPassword123')
        ->set('new_password', 'brandNewPassword99!')
        ->set('new_password_confirmation', 'brandNewPassword99!')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('isOpen', false);

    $associate->refresh();
    expect(Hash::check('brandNewPassword99!', $associate->password))->toBeTrue();
});

test('ChangePasswordModal rejects incorrect current password', function () {
    $associate = makeAssociateUser(['password' => Hash::make('originalPassword123')]);
    $this->actingAs($associate);

    Livewire::test(ChangePasswordModal::class)
        ->call('open')
        ->set('current_password', 'wrongCurrentPassword')
        ->set('new_password', 'brandNewPassword99!')
        ->set('new_password_confirmation', 'brandNewPassword99!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    $associate->refresh();
    expect(Hash::check('originalPassword123', $associate->password))->toBeTrue();
});

test('artisan command staff:create registers an emergency staff user', function () {
    $email = 'artisan-'.uniqid().'@isaleeeko.ca';

    $this->artisan("staff:create {$email} --name=\"Terminal Admin\" --password=\"ArtisanPass123!\" --role=manager")
        ->expectsOutputToContain('Staff account [Terminal Admin] successfully configured!')
        ->assertSuccessful();

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Terminal Admin');
    expect($user->role)->toBe('manager');
    expect($user->is_admin)->toBeTrue();
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('ArtisanPass123!', $user->password))->toBeTrue();
});
