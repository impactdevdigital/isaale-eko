<?php

use App\Livewire\Store\CartDrawer;
use App\Livewire\Store\CartPage;
use App\Livewire\Store\Kitchen\CookOnABudget;
use App\Models\Ingredient;
use App\Models\IngredientProduct;
use App\Models\MealCombo;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Recipe;
use App\Services\Baskets\BudgetBuilder;
use App\Support\Cart;
use Livewire\Livewire;

function budgetProduct(string $name, int $priceCents, ?int $stock = 50, string $size = 'Pack'): ProductVariation
{
    $product = Product::create(['name' => $name, 'slug' => str($name)->slug(), 'item_class' => 'grocery']);

    return $product->variations()->create(['name' => $size, 'price_cents' => $priceCents, 'stock_quantity' => $stock]);
}

/**
 * @param  array<int, array{0: ProductVariation, 1?: float}>  $options  [variation, pack factor], best first
 */
function budgetIngredient(string $name, array $options): Ingredient
{
    $ingredient = Ingredient::create(['name' => $name, 'slug' => str($name)->slug()]);

    foreach ($options as $rank => $option) {
        IngredientProduct::create([
            'ingredient_id' => $ingredient->id,
            'product_variation_id' => $option[0]->id,
            'rank' => $rank + 1,
            'pack_factor' => $option[1] ?? 1,
        ]);
    }

    return $ingredient;
}

/**
 * @param  array<int, array{0: Ingredient, 1: int, 2?: string}>  $ingredients  [ingredient, people per pack, role]
 */
function budgetRecipe(string $name, string $cuisine, array $ingredients, array $attributes = []): Recipe
{
    $recipe = Recipe::create([
        'name' => $name,
        'slug' => str($name)->slug(),
        'cuisine' => $cuisine,
        'status' => Recipe::STATUS_APPROVED,
        'base_servings' => 4,
        ...$attributes,
    ]);

    foreach ($ingredients as $i => $row) {
        $recipe->ingredients()->create([
            'ingredient_id' => $row[0]->id,
            'people_per_pack' => $row[1],
            'role' => $row[2] ?? 'required',
            'sort_order' => $i,
        ]);
    }

    return $recipe;
}

beforeEach(function () {
    $this->premiumRice = budgetProduct('Mama Gold Rice', 1499, size: '2kg');
    $this->budgetRice = budgetProduct('Value Long Grain Rice', 899, size: '2kg');
    $this->tomato = budgetProduct('Gino Tomato Paste', 399);
    $this->chicken = budgetProduct('Chicken Thighs', 1299, stock: 3);
    $this->plantain = budgetProduct('Green Plantain', 349);
    $this->palmOil = budgetProduct('Red Palm Oil', 899);
    $this->egusi = budgetProduct('Ground Egusi', 799, stock: 0);
    $this->peas = budgetProduct('Gungo Peas', 299);

    $rice = budgetIngredient('Rice', [[$this->premiumRice], [$this->budgetRice]]);
    $tomato = budgetIngredient('Tomato paste', [[$this->tomato]]);
    $chicken = budgetIngredient('Chicken', [[$this->chicken]]);
    $plantain = budgetIngredient('Plantain', [[$this->plantain]]);
    $palmOil = budgetIngredient('Palm oil', [[$this->palmOil]]);
    $egusi = budgetIngredient('Egusi', [[$this->egusi]]);
    $peas = budgetIngredient('Gungo peas', [[$this->peas]]);

    $this->jollof = budgetRecipe('Party Jollof', 'nigerian', [[$rice, 4], [$tomato, 4], [$chicken, 4], [$plantain, 2, 'optional']], ['is_featured' => true]);
    $this->dodo = budgetRecipe('Fried Plantain', 'west_african', [[$plantain, 2], [$palmOil, 8]]);
    $this->egusiSoup = budgetRecipe('Egusi Soup', 'nigerian', [[$egusi, 4], [$palmOil, 8]]);
    $this->riceAndPeas = budgetRecipe('Rice and Peas', 'caribbean', [[$rice, 4], [$peas, 4]], ['diet_tags' => ['vegan']]);

    $combo = MealCombo::create(['name' => 'Sunday Spread', 'slug' => 'sunday-spread']);
    $combo->recipes()->attach([$this->jollof->id => ['sort_order' => 0], $this->dodo->id => ['sort_order' => 1]]);
});

test('done when: for a set of budgets and party sizes every option fits the budget, is available today and costs the same in the real basket', function () {
    $engine = app(BudgetBuilder::class);
    $checked = 0;

    foreach ([800, 1500, 2500, 4000, 6000, 10000] as $budget) {
        foreach ([1, 2, 4, 6, 8] as $people) {
            $plan = $engine->meals($budget, $people);

            foreach ($plan->options as $option) {
                expect($option->totalCents())->toBeLessThanOrEqual($budget);

                foreach ($option->lines as $line) {
                    $variation = $line['variation']->fresh('product');
                    expect($variation->isOrderable() && $variation->product->isAvailable())->toBeTrue()
                        ->and($variation->stock_quantity === null || $line['quantity'] <= $variation->stock_quantity)->toBeTrue();
                }

                session()->forget('cart.lines');
                $engine->addToCart($option);
                expect(app(Cart::class)->subtotalCents())->toBe($option->totalCents());
                $checked++;
            }
        }
    }

    expect($checked)->toBeGreaterThan(20);
});

test('meals are scaled to the party, ranked, varied and capped at three, with the reason a meal was dropped', function () {
    $plan = app(BudgetBuilder::class)->meals(6000, 4);

    expect($plan->options)->toHaveCount(3)
        ->and($plan->options->map->cuisine()->unique())->toHaveCount(3)
        ->and($plan->dropped->firstWhere('name', 'Egusi Soup')['reason'])->toBe('Egusi isn’t available today');

    // Jollof for 4: one of each pack (best-ranked rice), optional plantain left out.
    $jollof = app(BudgetBuilder::class)->option('recipe:'.$this->jollof->id, 4, 6000);
    expect($jollof->totalCents())->toBe(1499 + 399 + 1299);

    // For 8 people it doubles.
    expect(app(BudgetBuilder::class)->option('recipe:'.$this->jollof->id, 8, 10000)->totalCents())->toBe(2 * (1499 + 399 + 1299));
});

test('when the best products are over budget the cheaper approved option is used', function () {
    $option = app(BudgetBuilder::class)->meals(2800, 4, 'nigerian')->options->first();

    expect($option->name)->toBe('Party Jollof')
        ->and($option->totalCents())->toBe(899 + 399 + 1299)
        ->and($option->lines->pluck('variation.id'))->toContain($this->budgetRice->id);
});

test('if nothing fits, the closest meal is shown with how far over it is', function () {
    Livewire::test(CookOnABudget::class)
        ->set('budget', '5')
        ->set('cuisine', 'caribbean')
        ->assertSee('Nothing fits')
        ->assertSee('Rice and Peas')
        ->assertSee(money(899 + 299 - 500).' over your budget');
});

test('cuisine and diet filters narrow the candidates', function () {
    $engine = app(BudgetBuilder::class);

    expect($engine->meals(5000, 4, 'caribbean')->options->pluck('name')->all())->toBe(['Rice and Peas'])
        ->and($engine->meals(5000, 4, null, 'vegan')->options->pluck('name')->all())->toBe(['Rice and Peas'])
        ->and($engine->dietTags())->toBe(['vegan']);
});

test('shop this meal adds the priced items and sets the budget on the basket', function () {
    Livewire::test(CookOnABudget::class)
        ->set('budget', '50')
        ->set('people', 4)
        ->assertSee('Shop this meal')
        ->call('shop', 'combo:'.MealCombo::first()->id)
        ->assertSee('Added everything for Sunday Spread')
        ->assertDispatched('cart-open');

    // Jollof (best rice) + two plantain + one palm oil for 4 people.
    expect(app(Cart::class)->subtotalCents())->toBe(1499 + 399 + 1299 + 2 * 349 + 899)
        ->and(app(BudgetBuilder::class)->budget())->toBe(5000);
});

test('the basket shows what is left of the budget, and when over offers changes that only apply when tapped', function () {
    $small = $this->premiumRice->product->variations()->create(['name' => '1kg', 'price_cents' => 799, 'stock_quantity' => 10]);

    $cart = app(Cart::class);
    $cart->add($this->premiumRice->id, 2); // 2998
    $cart->add($this->tomato->id);        // 399

    $page = Livewire::test(CartPage::class)
        ->set('budgetInput', '40')
        ->call('setBudget')
        ->assertSee('You have '.money(4000 - 3397).' left');

    Livewire::test(CartDrawer::class)->assertSee('You have '.money(603).' left of '.money(4000));

    $page->set('budgetInput', '30')->call('setBudget')
        ->assertSee(money(397).' over budget')
        ->assertSee('Choose Mama Gold Rice 1kg instead of 2kg')
        ->assertSee('Swap Mama Gold Rice for Value Long Grain Rice 2kg')
        ->assertSee('Buy one less Mama Gold Rice');

    // Nothing has changed yet.
    expect($cart->quantity($this->premiumRice->id))->toBe(2);

    // A key we wouldn't offer is ignored.
    $page->call('applySuggestion', 'remove:'.$this->chicken->id);
    expect($cart->subtotalCents())->toBe(3397);

    $page->call('applySuggestion', 'swap:'.$this->premiumRice->id.':'.$this->budgetRice->id)
        ->assertSee('You have '.money(3000 - 2 * 899 - 399).' left');

    expect($cart->quantity($this->premiumRice->id))->toBe(0)
        ->and($cart->quantity($this->budgetRice->id))->toBe(2)
        ->and($small->exists)->toBeTrue();
});

test('the cook on a budget page starts from what is left in the basket budget', function () {
    app(BudgetBuilder::class)->setBudget(5000);
    app(Cart::class)->add($this->tomato->id);

    Livewire::test(CookOnABudget::class)->assertSet('budget', '46.01');

    $this->get(route('kitchen.budget'))->assertOk()->assertSee('What can I cook with');
});
