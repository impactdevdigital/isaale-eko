<?php

namespace Database\Seeders;

use App\Models\Story;
use Illuminate\Database\Seeder;

/**
 * Demo Food Stories and Brand Updates from the Figma file (07.03–07.05).
 * Safe to run again: existing slugs are left as staff edited them.
 */
class StorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->foodStories() as $i => $story) {
            Story::firstOrCreate(['slug' => $story['slug']], $story + [
                'type' => Story::TYPE_FOOD,
                'status' => Story::STATUS_PUBLISHED,
                'published_at' => now()->subDays(2 + $i * 3)->setTime(9, 0),
                'allow_comments' => true,
            ]);
        }

        foreach ($this->brandUpdates() as $i => $update) {
            Story::firstOrCreate(['slug' => $update['slug']], $update + [
                'type' => Story::TYPE_UPDATE,
                'status' => Story::STATUS_PUBLISHED,
                'published_at' => now()->subDays(1 + $i * 4)->setTime(10, 0),
                'allow_comments' => false,
            ]);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foodStories(): array
    {
        return [
            [
                'slug' => 'the-scotch-bonnet-heat-with-a-heart',
                'title' => 'The Scotch Bonnet: Heat With A Heart',
                'excerpt' => 'It’s fruity, aromatic and the single ingredient that makes West African and Caribbean cooking taste like itself.',
                'cover_image' => 'images/store/stories/scotch-bonnet-cover.webp',
                'author_name' => 'Amina Yusuf',
                'read_minutes' => 5,
                'body' => <<<'MD'
***Small, bright and fiery. But that's only half the story.***

Ask anyone who grew up with West African or Caribbean food and they'll tell you: a pot of stew without scotch bonnet just doesn't taste right.

It isn't only about heat. Scotch bonnet brings a fruity, almost floral sweetness that no other pepper quite matches.

## A Pepper With Two Homes

From Lagos markets to Kingston kitchens, the scotch bonnet sits at the heart of two great food cultures. It finds its way into pepper soup, jerk marinades, stews and sauces.

## How to Cook With It

Use it whole for gentle warmth, slice it for more kick, or blend it into your base for the full experience. Remove the seeds if you want the flavour without all the fire.

Whatever you cook, a little goes a long way.
MD,
            ],
            [
                'slug' => 'why-jollof-rice-is-never-just-rice',
                'title' => 'Why Jollof Rice Is Never Just Rice',
                'excerpt' => 'Behind every pot is a family, a debate and a standard that can never quite be met.',
                'cover_image' => 'images/store/stories/jollof-cover.webp',
                'read_minutes' => 4,
                'body' => <<<'MD'
***Some dishes fill your stomach. Others take you home.***

There's something about the smell of jollof rice that brings everyone to the kitchen.

You could be minding your business when that familiar aroma of tomatoes, peppers and spices fills the house. Suddenly, you're asking, "Is the jollof ready?"

And if you grew up in an African household, you already know the response.

"Does it look ready?"

You quietly retreat.

But that's jollof for you. It's never just food.

## Every Pot Tells a Story

Think about Nigerian parties. The music, the laughter, the aunties adjusting their gele, and that glorious moment when the jollof finally arrives.

Even those who claimed they weren't hungry suddenly want "small rice."

Then there's the legendary Nigeria versus Ghana debate. Different recipes, different techniques, but the same pride in a dish that connects generations.

## A Taste of Home

For Africans living abroad, jollof carries something deeper.

One familiar aroma can transport you from a cold Canadian afternoon to your mother's kitchen in Lagos.

It's the memories, the celebrations, the people.

At Ìsàlèè Èkó, we believe food is more than ingredients. It's a connection to where we come from.

Because the best part of jollof isn't always what's on your plate. It's who you share it with.
MD,
            ],
            [
                'slug' => 'how-one-pot-of-jollof-rescued-the-party',
                'title' => 'How One Pot Of Jollof Rescued The Party',
                'excerpt' => 'When the power went out an hour before the party, Amina turned a stressful situation into an unforgettable celebration with one pot of jollof and a lot of heart.',
                'cover_image' => 'images/store/stories/party-jollof-cover.webp',
                'author_name' => 'Amina Yusuf',
                'read_minutes' => 6,
                'body' => <<<'MD'
***An hour to go, forty guests on the way, and the lights went out.***

Amina had planned everything: the small chops, the drinks, the playlist. Then the power cut out.

Instead of panicking, she lit the gas burner, pulled out her biggest pot and did what her mother taught her.

## One Pot, Many Hands

Neighbours arrived with torches. Someone started chopping onions. Someone else stirred. Before long, the kitchen was the party.

## The Best Jollof Yet

By the time the lights came back on, the rice was smoky, rich and perfect. Everyone agreed it was the best jollof they'd had all year.

Sometimes the best celebrations are the ones that don't go to plan.
MD,
            ],
            [
                'slug' => 'meet-madam-ellen-turning-family-recipes-into-a-brighter-future',
                'title' => 'Meet Madam Ellen: Turning Family Recipes Into A Brighter Future',
                'excerpt' => 'How Madam Ellen is keeping her mother’s recipes alive while building community through food.',
                'cover_image' => 'images/store/stories/madam-ellen.webp',
                'read_minutes' => 4,
                'is_trending' => true,
                'body' => <<<'MD'
Madam Ellen learned to cook standing on a stool beside her mother. Today she cooks for her whole community.

## Recipes Worth Keeping

Every recipe she makes was passed down by hand, never written. Now she's teaching the next generation so nothing is lost.

## Food That Brings People Together

For Madam Ellen, every meal is an invitation. Pull up a chair, there's always enough.
MD,
            ],
            [
                'slug' => 'the-humble-plantain-a-taste-of-home-across-generations',
                'title' => 'The Humble Plantain: A Taste Of Home Across Generations',
                'excerpt' => 'From roadside stalls to family tables, the plantain tells a story of resilience and creativity.',
                'cover_image' => 'images/store/stories/plantain.webp',
                'read_minutes' => 6,
                'is_trending' => true,
                'body' => <<<'MD'
Green, yellow or almost black: every stage of the plantain has its own dish, and every family has a favourite.

## Dodo, Kelewele and More

Fried sweet and golden, spiced with ginger and pepper, or boiled and served with stew, plantain fits every meal of the day.

## Always on the Table

It travels well, keeps well and tastes like home wherever you are.
MD,
            ],
            [
                'slug' => 'more-than-a-drink-the-story-of-zobo',
                'title' => 'More Than A Drink: The Story Of Zobo',
                'excerpt' => 'A refreshing drink with deep roots, zobo connects people, culture and wellness.',
                'cover_image' => 'images/store/stories/zobo.webp',
                'read_minutes' => 5,
                'is_trending' => true,
                'body' => <<<'MD'
Deep red, lightly spiced and best served cold, zobo is made from dried hibiscus petals simmered with ginger, cloves and pineapple.

## A Drink for Every Occasion

You'll find it at weddings, naming ceremonies and on hot afternoons at home.

## Make It Your Way

Sweeten it with honey, add a little citrus or keep it sharp. Every family has its own recipe.
MD,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function brandUpdates(): array
    {
        return [
            [
                'slug' => 'were-closing-for-the-holidays',
                'title' => 'We’ll Be Closed For The Holidays',
                'excerpt' => 'Our team is taking a short break to spend time with family. Check our holiday hours and plan your pickup or delivery before we close.',
                'cover_image' => 'images/store/updates/holiday-closure.webp',
                'body' => "Our team is taking a short break over the holidays to spend time with family.\n\n## Plan Ahead\n\nPlace your orders early so you have everything you need for your celebrations. Our opening hours on the site always show the latest times.\n\nThank you for shopping with Ìsàlèè Èkó. See you in the new year!",
            ],
            [
                'slug' => 'new-arrivals-more-favourites-from-home',
                'title' => 'New Arrivals: More Favourites From Home',
                'excerpt' => 'We’ve added more of the brands and pantry staples you asked for, from seasonings and snacks to fresh produce.',
                'cover_image' => 'images/store/updates/new-arrivals.webp',
                'body' => "You asked, we listened. We've added more of your favourite brands and pantry staples to the shelves.\n\nBrowse **New Arrivals** in the shop to see what's just landed.",
            ],
            [
                'slug' => 'black-friday-deals-are-here',
                'title' => 'Black Friday Deals Are Here!',
                'excerpt' => 'Stock up on your favourites for less. Our biggest deals of the year are live for a limited time, in store and online.',
                'cover_image' => 'images/store/updates/black-friday.webp',
                'body' => "Our biggest deals of the year are here for a limited time.\n\nHead to **Special Deals** in the shop to see everything on offer, and stock up before they're gone.",
            ],
            [
                'slug' => 'giving-back-this-season',
                'title' => 'Giving Back This Season',
                'excerpt' => 'This season we’re partnering with local groups to share good food with families in our community.',
                'cover_image' => 'images/store/updates/giving-back.webp',
                'body' => "Food is how we show love, and this season we're sharing it with our community.\n\nWe're working with local groups to make sure more families have a good meal on the table. Thank you for being part of it.",
            ],
        ];
    }
}
