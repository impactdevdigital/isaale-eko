<?php

namespace Tests\Feature;

use App\Livewire\Admin\AdminLogin;
use App\Livewire\Admin\Dashboard;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

function makeAdminUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Store Operations Admin',
        'email' => 'admin-test-'.uniqid().'@isaleeeko.ca',
        'password' => Hash::make('secret123'),
        'is_admin' => true,
        'role' => User::ROLE_MANAGER,
        'is_active' => true,
        'email_verified_at' => now(),
    ], $attributes));
}

function makeCustomerUser(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Regular Shopper',
        'email' => 'customer-test-'.uniqid().'@gmail.com',
        'password' => Hash::make('secret123'),
        'is_admin' => false,
    ], $attributes));
}

test('guest is redirected to admin login when attempting to access admin portal', function () {
    $this->get('/admin')
        ->assertRedirect(route('admin.login'));

    $this->get('/admin/orders')
        ->assertRedirect(route('admin.login'));

    $this->get('/admin/products')
        ->assertRedirect(route('admin.login'));
});

test('non-admin customer is forbidden from accessing admin portal', function () {
    $customer = makeCustomerUser();

    $this->actingAs($customer)
        ->get('/admin')
        ->assertStatus(403);
});

test('admin can access operations dashboard', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertStatus(200)
        ->assertSee('Operations Command Center')
        ->assertSee('Store Operations Admin');
});

test('admin login component renders and shows credentials form', function () {
    Livewire::test(AdminLogin::class)
        ->assertStatus(200)
        ->assertSee('Operations Portal')
        ->assertSee('Staff Email Address')
        ->assertSee('Password');
});

test('admin login rejects invalid credentials', function () {
    $admin = makeAdminUser();

    Livewire::test(AdminLogin::class)
        ->set('email', $admin->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

test('admin login rejects non-admin users even with correct password', function () {
    $customer = makeCustomerUser(['password' => Hash::make('password123')]);

    Livewire::test(AdminLogin::class)
        ->set('email', $customer->email)
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

test('admin login succeeds with valid admin credentials and redirects to dashboard', function () {
    $admin = makeAdminUser(['password' => Hash::make('correct-password')]);

    Livewire::test(AdminLogin::class)
        ->set('email', $admin->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($admin->id);
});

test('admin logout terminates session and redirects to admin login', function () {
    $admin = makeAdminUser();

    $this->actingAs($admin)
        ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));

    expect(Auth::check())->toBeFalse();
});

test('operations dashboard computes live kpi metrics and urgent orders correctly', function () {
    $admin = makeAdminUser();

    // Create an order placed today
    $order = Order::create([
        'number' => 'ORD9988',
        'status' => Order::STATUS_PLACED,
        'fulfilment' => 'pickup',
        'contact_name' => 'Chioma Adeyemi',
        'subtotal_cents' => 4500,
        'total_cents' => 4500,
        'payment_method' => 'card',
        'placed_at' => now(),
    ]);

    // Create a product with low stock
    $product = Product::firstOrCreate(['slug' => 'test-stock-rice'], ['name' => 'Test Stock Rice']);
    $variation = ProductVariation::create([
        'product_id' => $product->id,
        'name' => '5kg Bag',
        'sku' => 'TEST-RICE-5KG',
        'price_cents' => 2500,
        'stock_quantity' => 2,
    ]);

    $this->actingAs($admin);

    Livewire::test(Dashboard::class)
        ->assertStatus(200)
        ->assertSee(money(4500))
        ->assertSee('#ORD9988')
        ->assertSee('Chioma Adeyemi')
        ->assertSee('Test Stock Rice')
        ->assertSee('2 left');
});
