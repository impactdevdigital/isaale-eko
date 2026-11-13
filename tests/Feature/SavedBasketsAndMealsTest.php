<?php

use App\Livewire\Store\Account\BuyAgain;
use App\Livewire\Store\Account\RebuildBasket;
use App\Livewire\Store\Account\SavedBaskets;
use App\Livewire\Store\Account\SavedMeals;
use App\Livewire\Store\CartPage;
use App\Livewire\Store\Kitchen\RecipeDetail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Models\SavedBasket;
use App\Models\User;
use App\Services\Baskets\BasketLine;
use App\Services\Baskets\BasketRepricer;
use App\Services\Baskets\Usuals;
use App\Support\Cart;
use Carbon\CarbonInterface;
use Livewire\Livewire;

function basketVariation(string $name, int $priceCents, ?int $stock = 20, string $size = '1kg'): ProductVariation
{
    $product = Product::create([
        'name' => $name,
        'slug' => str($name)->slug(),
        'item_class' => 'grocery',
        'image' => 'images/store/products/'.str($name)->slug().'.webp',
    ]);

    return $product->variations()->create(['name' => $size, 'price_cents' => $priceCents, 'stock_quantity' => $stock]);
}

/**
 * @param  array<int, array{0: ProductVariation, 1: int}>  $lines  [variation, quantity]
 */
function basketOrder(User $user, array $lines, ?CarbonInterface $placedAt = null): Order
{
    $total = collect($lines)->sum(fn ($line) => $line[0]->price_cents * $line[1]);

    $order = Order::create([
        'number' => 'ISA-'.strtoupper(str()->random(6)),
        'user_id' => $user->id,
        'status' => Order::STATUS_PAID,
        'fulfilment' => 'pickup',
        'contact_name' => $user->name,
        'contact_email' => $user->email,
        'subtotal_cents' => $total,
        'total_cents' => $total,
        'payment_method' => 'card',
        'placed_at' => $placedAt ?? now(),
    ]);

    foreach ($lines as [$variation, $quantity]) {
        $order->items()->create([
            'product_id' => $variation->product_id,
            'product_variation_id' => $variation->id,
            'name' => $variation->product->name,
            'variation_name' => $variation->name,
            'unit_price_cents' => $variation->price_cents,
            'quantity' => $quantity,
            'line_total_cents' => $variation->price_cents * $quantity,
        ]);
    }

    return $order;
}

test('done when: a customer with two orders sees their usuals, saves a basket, comes back and rebuilds it at current prices', function () {
    $user = User::factory()->create();
    $rice = basketVariation('Golden Sella Rice', 2499);
    $plantain = basketVariation('Green Plantain', 349);
    $palmOil = basketVariation('Red Palm Oil', 899);

    basketOrder($user, [[$rice, 1], [$plantain, 4]], now()->subWeeks(2));
    basketOrder($user, [[$rice, 1], [$palmOil, 1]], now()->subDays(3));

    $this->actingAs($user);

    // Usuals: rice was in both orders, so it leads.
    Livewire::test(BuyAgain::class)
        ->assertSee('Your usuals')
        ->assertSeeInOrder(['Golden Sella Rice', 'Green Plantain'])
        ->assertSee('Reorder your usuals');

    // Save today's basket.
    $cart = app(Cart::class);
    $cart->add($rice->id, 2);
    $cart->add($plantain->id, 3);

    Livewire::test(CartPage::class)
        ->set('basketName', 'Weekly shop')
        ->call('saveBasket')
        ->assertHasNoErrors()
        ->assertSee('Saved “Weekly shop”');

    $basket = SavedBasket::where('user_id', $user->id)->sole();
    expect($basket->items)->toHaveCount(2)
        ->and($basket->savedTotalCents())->toBe(2 * 2499 + 3 * 349);

    // Close the browser: the session basket is gone. Prices move in the meantime.
    $cart->clear($rice->id, $plantain->id);
    $this->flushSession();
    $rice->update(['price_cents' => 2599]);

    Livewire::test(SavedBaskets::class)
        ->assertSee('Weekly shop')
        ->assertSee(money(2 * 2499 + 3 * 349))
        ->assertSee(money(2 * 2599 + 3 * 349));

    Livewire::test(RebuildBasket::class, ['basket' => $basket])
        ->assertSee('When you saved it')
        ->assertSee(money(2 * 2499 + 3 * 349))
        ->assertSee('Was '.money(2499).', now '.money(2599))
        ->call('decrement', 'l1') // one fewer plantain before checkout
        ->call('addToBasket')
        ->assertRedirect(route('cart'));

    $lines = app(Cart::class)->lines();
    expect($lines[$rice->id]->quantity)->toBe(2)
        ->and($lines[$plantain->id]->quantity)->toBe(2)
        ->and(app(Cart::class)->subtotalCents())->toBe(2 * 2599 + 2 * 349);
});

test('usuals rank by how many orders an item appears in, skip old orders and reorder at usual quantities', function () {
    $user = User::factory()->create();
    $rice = basketVariation('Golden Sella Rice', 2499);
    $plantain = basketVariation('Green Plantain', 349);
    $garri = basketVariation('Ijebu Garri', 699, stock: 0);
    $old = basketVariation('Old Favourite', 500);

    basketOrder($user, [[$plantain, 4], [$garri, 1]], now()->subMonth());
    basketOrder($user, [[$plantain, 2], [$rice, 1]], now()->subWeek());
    basketOrder($user, [[$old, 1]], now()->subMonths(7));

    $lines = app(Usuals::class)->linesFor($user);

    expect($lines->pluck('variation_id')->all())->toBe([$plantain->id, $rice->id, $garri->id])
        ->and($lines->first()['quantity'])->toBe(3); // (4 + 2) / 2 orders

    $this->actingAs($user);

    Livewire::test(BuyAgain::class)
        ->assertSee('Not available today — left out')
        ->call('reorderUsuals')
        ->assertDispatched('cart-open');

    $cart = app(Cart::class);
    expect($cart->quantity($plantain->id))->toBe(3)
        ->and($cart->quantity($rice->id))->toBe(1)
        ->and($cart->quantity($garri->id))->toBe(0);
});

test('rebuilding a past order flags what is unavailable and only swaps in a substitute when the customer taps it', function () {
    $user = User::factory()->create();
    $small = basketVariation('Ayoola Poundo Yam', 1299, size: '1.8kg');
    $large = $small->product->variations()->create(['name' => '4.5kg', 'price_cents' => 2899, 'stock_quantity' => 5, 'sort_order' => 1]);
    $plantain = basketVariation('Green Plantain', 349);

    $order = basketOrder($user, [[$small, 1], [$plantain, 2]]);
    $small->update(['stock_quantity' => 0]);

    expect(app(BasketRepricer::class)->reprice([
        ['key' => 'a', 'variation_id' => $small->id, 'quantity' => 1, 'name' => 'Poundo'],
    ])->first())
        ->status->toBe(BasketLine::UNAVAILABLE)
        ->substitute->id->toBe($large->id);

    $this->actingAs($user);

    // Without tapping the swap, the unavailable item is left out.
    Livewire::test(RebuildBasket::class, ['order' => $order])
        ->assertSee('Your last basket')
        ->assertSee('Not available today')
        ->assertSee('Swap for Ayoola Poundo Yam 4.5kg')
        ->call('addToBasket');

    expect(app(Cart::class)->quantity($large->id))->toBe(0)
        ->and(app(Cart::class)->quantity($plantain->id))->toBe(2);

    app(Cart::class)->clear($plantain->id);

    Livewire::test(RebuildBasket::class, ['order' => $order])
        ->call('toggleSubstitute', 'l0')
        ->assertSee('Using Ayoola Poundo Yam 4.5kg')
        ->call('remove', 'l1')
        ->call('addToBasket');

    expect(app(Cart::class)->quantity($large->id))->toBe(1)
        ->and(app(Cart::class)->quantity($plantain->id))->toBe(0);
});

test('guests are nudged to create an account to save a basket, never forced', function () {
    app(Cart::class)->add(basketVariation('Green Plantain', 349)->id);

    Livewire::test(CartPage::class)
        ->assertSee('to save this basket and rebuild it later')
        ->assertSee('Proceed to checkout')
        ->set('basketName', 'Weekly shop')
        ->call('saveBasket')
        ->assertDispatched('sign-in-open');

    expect(SavedBasket::count())->toBe(0);
});

test('customers can only open their own saved baskets and orders', function () {
    $owner = User::factory()->create();
    $basket = $owner->savedBaskets()->create(['name' => 'Mine']);
    $order = basketOrder($owner, [[basketVariation('Green Plantain', 349), 1]]);

    $this->actingAs(User::factory()->create());

    $this->get(route('account.saved-baskets.show', $basket))->assertNotFound();
    $this->get(route('account.orders.rebuild', $order))->assertNotFound();

    $this->actingAs($owner);
    $this->get(route('account.saved-baskets.show', $basket))->assertOk()->assertSee('Mine');
    $this->get(route('account.orders.rebuild', $order))->assertOk()->assertSee('Your last basket');

    Livewire::test(SavedBaskets::class)->call('delete', $basket->id);
    expect(SavedBasket::count())->toBe(0);
});

test('recipes can be saved as favourite meals and listed in the account', function () {
    $recipe = Recipe::create(['name' => 'Party Jollof', 'slug' => 'party-jollof', 'status' => Recipe::STATUS_APPROVED, 'base_servings' => 4]);

    // Guests are asked to sign in.
    Livewire::test(RecipeDetail::class, ['recipe' => $recipe])
        ->call('toggleSaved')
        ->assertDispatched('sign-in-open');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(RecipeDetail::class, ['recipe' => $recipe])
        ->assertSee('Save meal')
        ->call('toggleSaved')
        ->assertSee('Saved');

    Livewire::test(SavedMeals::class)
        ->assertSee('Party Jollof')
        ->call('remove', $recipe->id)
        ->assertSee('No saved meals yet');

    expect($user->savedRecipes()->count())->toBe(0);
});
