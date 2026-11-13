<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Recipe;

/**
 * Header navigation and mega menu content (layout from the Figma "Core Navigation" screens).
 */
class StoreNavigation
{
    /**
     * @return array<int, array{label: string, menu?: string, url?: string}>
     */
    public static function links(): array
    {
        return [
            ['label' => 'Shop', 'menu' => 'shop'],
            ['label' => 'Kitchen & Recipes', 'url' => route('kitchen.index')],
            ['label' => 'Discover', 'menu' => 'discover'],
            ['label' => 'Help', 'menu' => 'help'],
            ['label' => 'Country', 'url' => route('home').'#shop-by-country'],
            ['label' => 'New arrivals', 'url' => route('shop', ['sort' => 'new'])],
            ['label' => 'Best sellers', 'url' => route('shop', ['sort' => 'bestsellers'])],
            ['label' => 'Special deals', 'url' => route('shop', ['sort' => 'discounted'])],
            ['label' => 'Community', 'url' => route('community')],
            ['label' => 'About', 'url' => '#'],
        ];
    }

    /**
     * Each menu has sidebar links; the first one owns the panel content.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function menus(): array
    {
        return [
            'shop' => [
                'sidebar' => [
                    'Categories' => route('shop'),
                    'Country' => route('home').'#shop-by-country',
                    'New arrivals' => route('shop', ['sort' => 'new']),
                    'Best sellers' => route('shop', ['sort' => 'bestsellers']),
                    'Wholesale' => '#',
                ],
                'eyebrow' => 'Start with what you need',
                'title' => 'Shop Categories',
                'link' => 'All products',
                'url' => route('shop'),
                'groups' => Category::roots()
                    ->has('children')
                    ->with('children')
                    ->get()
                    ->mapWithKeys(fn (Category $group) => [
                        $group->name => $group->children->map(fn (Category $child) => [
                            'name' => $child->name,
                            'image' => $child->image,
                            'url' => route('shop', ['category' => $child->slug]),
                        ])->all(),
                    ])
                    ->all(),
            ],
            'discover' => [
                'sidebar' => [
                    'All Recipes' => route('kitchen.index'),
                    'Cook With What I Have' => route('kitchen.pantry'),
                    'Cook on a Budget' => route('kitchen.budget'),
                    'Food Stories' => route('stories.index'),
                    'Brand Updates' => route('updates.index'),
                ],
                'eyebrow' => 'Discover Ìsàlèè Èkó',
                'title' => 'Cook Something Good',
                'link' => 'View all recipes',
                'url' => route('kitchen.index'),
                'recipes' => (Recipe::where('status', Recipe::STATUS_APPROVED)->exists() ? Recipe::approved() : Recipe::query())
                    ->with('country')
                    ->orderBy('sort_order')
                    ->take(4)
                    ->get()
                    ->map(fn (Recipe $recipe) => [
                        'name' => $recipe->title ?: $recipe->name,
                        'meta' => collect([$recipe->durationLabel(), $recipe->country?->name])->filter()->join(' · '),
                        'image' => $recipe->image ?: $recipe->thumbnail,
                        'url' => route('kitchen.recipe', $recipe),
                    ])
                    ->all(),
            ],
            'help' => [
                'sidebar' => ['Ask Asha' => '#', 'Delivery & pickup' => '#', 'FAQ' => '#', 'Contact us' => '#'],
                'eyebrow' => 'Here to help',
                'title' => 'Ask Asha Anything',
                'link' => 'Talk to Asha',
                'url' => '#',
                'body' => 'Get practical answers about ingredients, recipes, delivery and your order. Asha stays with you wherever you browse.',
            ],
        ];
    }
}
