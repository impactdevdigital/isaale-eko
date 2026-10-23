<?php

namespace Tests\Feature;

use App\Livewire\Admin\CategoriesManager;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

test('admin categories manager renders and displays hierarchy and kpis', function () {
    $parent = Category::create([
        'name' => 'Dry Food & Pantry Test',
        'slug' => 'dry-food-pantry-test',
        'square_id' => 'SQ_PARENT_DRY_001',
        'is_featured' => true,
    ]);

    $child = Category::create([
        'name' => 'Grains & Rice Test',
        'slug' => 'grains-rice-test',
        'parent_id' => $parent->id,
        'square_id' => 'SQ_CHILD_GRAINS_002',
        'is_featured' => false,
    ]);

    Livewire::test(CategoriesManager::class)
        ->assertStatus(200)
        ->assertSee('Categories')
        ->assertSee('Sub-Categories')
        ->assertSee('SQ_PARENT_DRY_001')
        ->assertSee('SQ_CHILD_GRAINS_002');
});

test('staff can search categories by name, slug, or Square POS ID', function () {
    $parent = Category::create([
        'name' => 'African Spices Test',
        'slug' => 'african-spices-test',
        'square_id' => 'SQ_SPICES_777',
    ]);

    $child = Category::create([
        'name' => 'Suya Pepper Test',
        'slug' => 'suya-pepper-test',
        'parent_id' => $parent->id,
        'square_id' => 'SQ_SUYA_888',
    ]);

    $otherParent = Category::create([
        'name' => 'Beverages Unrelated',
        'slug' => 'beverages-unrelated',
        'square_id' => 'SQ_BEV_999',
    ]);

    Livewire::test(CategoriesManager::class)
        ->set('search', 'SQ_SUYA_888')
        ->assertSee('Suya Pepper Test')
        ->assertDontSee('SQ_BEV_999');
});

test('staff can switch view modes between hierarchy, all sub-categories, and parents', function () {
    $parent = Category::create([
        'name' => 'Bakery & Snacks Test',
        'slug' => 'bakery-snacks-test',
        'square_id' => 'SQ_BAKERY_101',
    ]);

    $child = Category::create([
        'name' => 'Agege Bread Test',
        'slug' => 'agege-bread-test',
        'parent_id' => $parent->id,
        'square_id' => 'SQ_BREAD_102',
    ]);

    // All Sub-categories mode
    Livewire::test(CategoriesManager::class)
        ->set('viewMode', 'all_sub')
        ->assertStatus(200)
        ->assertSee('Agege Bread Test')
        ->assertSee('SQ_BREAD_102');

    // Parent Departments mode
    Livewire::test(CategoriesManager::class)
        ->set('viewMode', 'parents')
        ->assertStatus(200)
        ->assertSee('Bakery & Snacks Test')
        ->assertSee('SQ_BAKERY_101')
        ->assertSee('1 sub-categories');
});

test('staff can toggle featured status on a category', function () {
    $cat = Category::create([
        'name' => 'Plantains & Tubers',
        'slug' => 'plantains-tubers',
        'is_featured' => false,
    ]);

    Livewire::test(CategoriesManager::class)
        ->call('toggleFeatured', $cat->id)
        ->assertHasNoErrors();

    expect($cat->fresh()->is_featured)->toBeTrue();

    // Toggle back
    Livewire::test(CategoriesManager::class)
        ->call('toggleFeatured', $cat->id)
        ->assertHasNoErrors();

    expect($cat->fresh()->is_featured)->toBeFalse();
});

test('staff can create a new sub-category linked to a parent category', function () {
    $parent = Category::create([
        'name' => 'Frozen Meats',
        'slug' => 'frozen-meats',
        'square_id' => 'SQ_FROZEN_111',
    ]);

    Livewire::test(CategoriesManager::class)
        ->call('openCreateModal', $parent->id)
        ->set('name', 'Smoked Turkey Test')
        ->set('slug', 'smoked-turkey-test')
        ->set('square_id', 'SQ_TURKEY_222')
        ->set('emoji', '🦃')
        ->set('is_featured', true)
        ->set('sort_order', 3)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $created = Category::where('slug', 'smoked-turkey-test')->first();
    expect($created)->not->toBeNull()
        ->and($created->parent_id)->toBe($parent->id)
        ->and($created->square_id)->toBe('SQ_TURKEY_222')
        ->and($created->emoji)->toBe('🦃')
        ->and($created->is_featured)->toBeTrue()
        ->and($created->sort_order)->toBe(3);
});

test('staff can edit an existing category attributes', function () {
    $cat = Category::create([
        'name' => 'Original Name',
        'slug' => 'original-name',
        'sort_order' => 1,
    ]);

    Livewire::test(CategoriesManager::class)
        ->call('openEditModal', $cat->id)
        ->set('name', 'Updated African Name')
        ->set('sort_order', 10)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    expect($cat->fresh()->name)->toBe('Updated African Name')
        ->and($cat->fresh()->sort_order)->toBe(10);
});

test('prevents deleting category if active products or children are assigned', function () {
    $parent = Category::create([
        'name' => 'Protected Parent Department',
        'slug' => 'protected-parent-dept',
    ]);

    $child = Category::create([
        'name' => 'Protected Child Category',
        'slug' => 'protected-child-cat',
        'parent_id' => $parent->id,
    ]);

    $product = Product::create([
        'name' => 'Attached Shelf Item',
        'slug' => 'attached-shelf-item',
        'category_id' => $child->id,
    ]);

    // Attempt deleting child with product
    Livewire::test(CategoriesManager::class)
        ->call('confirmDelete', $child->id)
        ->call('deleteCategory')
        ->assertSee('Cannot delete');

    expect(Category::whereKey($child->id)->exists())->toBeTrue();

    // Attempt deleting parent with child
    Livewire::test(CategoriesManager::class)
        ->call('confirmDelete', $parent->id)
        ->call('deleteCategory')
        ->assertSee('Cannot delete');

    expect(Category::whereKey($parent->id)->exists())->toBeTrue();

    // Now remove product and child, then delete empty parent
    $product->delete();
    $child->delete();

    Livewire::test(CategoriesManager::class)
        ->call('confirmDelete', $parent->id)
        ->call('deleteCategory')
        ->assertHasNoErrors();

    expect(Category::whereKey($parent->id)->exists())->toBeFalse();
});
