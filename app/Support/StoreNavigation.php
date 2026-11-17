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
            ['label' => __('Shop'), 'menu' => 'shop'],
            ['label' => __('Kitchen & Recipes'), 'url' => route('kitchen.index')],
            ['label' => __('Discover'), 'menu' => 'discover'],
            ['label' => __('Help'), 'menu' => 'help'],
            ['label' => __('Country'), 'url' => route('home').'#shop-by-country'],
            ['label' => __('New arrivals'), 'url' => route('shop', ['sort' => 'new'])],
            ['label' => __('Best sellers'), 'url' => route('shop', ['sort' => 'bestsellers'])],
            ['label' => __('Community'), 'url' => route('community')],
            ['label' => __('About'), 'url' => '#'],
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
                    __('Categories') => route('shop'),
                    __('Country') => route('home').'#shop-by-country',
                    __('New arrivals') => route('shop', ['sort' => 'new']),
                    __('Best sellers') => route('shop', ['sort' => 'bestsellers']),
                    __('Wholesale') => '#',
                ],
                'eyebrow' => __('Start with what you need'),
                'title' => __('Shop Categories'),
                'link' => __('All products'),
                'url' => route('shop'),
                'groups' => Category::roots()
                    ->has('children')
                    ->with('children')
                    ->get()
                    ->mapWithKeys(fn (Category $group) => [
                        $group->localized_name => $group->children->map(fn (Category $child) => [
                            'name' => $child->localized_name,
                            'image' => $child->image,
                            'url' => route('shop', ['category' => $child->slug]),
                        ])->all(),
                    ])
                    ->all(),
            ],
            'discover' => [
                'sidebar' => [
                    __('All Recipes') => route('kitchen.index'),
                    __('Cook With What I Have') => route('kitchen.pantry'),
                    __('Cook on a Budget') => route('kitchen.budget'),
                    __('Food Stories') => route('stories.index'),
                    __('Brand Updates') => route('updates.index'),
                ],
                'eyebrow' => __('Discover Ìsàlèè Èkó'),
                'title' => __('Cook Something Good'),
                'link' => __('View all recipes'),
                'url' => route('kitchen.index'),
                'recipes' => (Recipe::where('status', Recipe::STATUS_APPROVED)->exists() ? Recipe::approved() : Recipe::query())
                    ->with('country')
                    ->orderBy('sort_order')
                    ->take(4)
                    ->get()
                    ->map(fn (Recipe $recipe) => [
                        'name' => $recipe->title ?: $recipe->name,
                        'meta' => collect([$recipe->durationLabel(), $recipe->country?->localized_name])->filter()->join(' · '),
                        'image' => $recipe->image ?: $recipe->thumbnail,
                        'url' => route('kitchen.recipe', $recipe),
                    ])
                    ->all(),
            ],
            'help' => [
                'sidebar' => [__('Ask Asha') => '#', __('Delivery & pickup') => '#', __('FAQ') => '#', __('Contact us') => '#'],
                'eyebrow' => __('Here to help'),
                'title' => __('Ask Asha Anything'),
                'link' => __('Talk to Asha'),
                'url' => '#',
                'body' => __('Get practical answers about ingredients, recipes, delivery and your order. Asha stays with you wherever you browse.'),
            ],
        ];
    }
}
