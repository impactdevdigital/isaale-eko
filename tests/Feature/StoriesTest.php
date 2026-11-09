<?php

use App\Livewire\Admin\StoriesManager;
use App\Livewire\Store\Stories\StoriesIndex;
use App\Livewire\Store\Stories\StoryShow;
use App\Models\Consent;
use App\Models\Story;
use App\Models\StoryComment;
use App\Models\User;
use Database\Seeders\StorySeeder;
use Livewire\Livewire;

function makeStory(array $attributes = []): Story
{
    static $n = 0;
    $n++;

    return Story::create(array_merge([
        'type' => Story::TYPE_FOOD,
        'title' => "Story {$n}",
        'slug' => "story-{$n}",
        'excerpt' => "Excerpt {$n}",
        'body' => "First paragraph.\n\n## A Heading\n\nSecond paragraph.",
        'status' => Story::STATUS_PUBLISHED,
        'published_at' => now()->subDays($n),
        'allow_comments' => true,
    ], $attributes));
}

test('the food stories page lists published food stories only, three per page', function () {
    foreach (range(1, 4) as $i) {
        makeStory(['title' => "Published {$i}"]);
    }
    makeStory(['title' => 'A Draft Story', 'status' => Story::STATUS_DRAFT]);
    makeStory(['title' => 'Scheduled Story', 'published_at' => now()->addDay()]);
    makeStory(['title' => 'Holiday Hours Update', 'type' => Story::TYPE_UPDATE]);

    $this->get(route('stories.index'))
        ->assertOk()
        ->assertSee('Food Stories')
        ->assertSee('Published 1')
        ->assertSee('Showing 1–3 of 4 results')
        ->assertDontSee('A Draft Story')
        ->assertDontSee('Scheduled Story')
        ->assertDontSee('Holiday Hours Update')
        ->assertSee('Trending Stories')
        ->assertSee('Get food stories in your inbox');

    Livewire::test(StoriesIndex::class)->call('nextPage')->assertSee('Published 4')->assertDontSee('Published 1</a>', false);
});

test('the brand updates page lists published updates', function () {
    $update = makeStory(['title' => 'New Arrivals Are In', 'type' => Story::TYPE_UPDATE]);
    makeStory(['title' => 'A Food Story']);

    $this->get(route('updates.index'))
        ->assertOk()
        ->assertSee('Brand Updates')
        ->assertSee('New Arrivals Are In')
        ->assertSee(route('updates.show', $update), false)
        ->assertDontSee('A Food Story');
});

test('a story page shows the article, and hides drafts and the wrong section from customers', function () {
    $story = makeStory(['title' => 'Why Jollof Rice Is Never Just Rice', 'body' => "Intro.\n\n## Every Pot Tells A Story\n\n<script>alert(1)</script>"]);

    $this->get(route('stories.show', $story))
        ->assertOk()
        ->assertSee('Why Jollof Rice Is Never Just Rice')
        ->assertSee('<h2>Every Pot Tells A Story</h2>', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Leave a Reply');

    // A food story isn't reachable under /brand-updates.
    $this->get(route('updates.show', $story))->assertNotFound();

    $draft = makeStory(['status' => Story::STATUS_DRAFT]);
    $this->get(route('stories.show', $draft))->assertNotFound();

    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]))
        ->get(route('stories.show', $draft))
        ->assertOk()
        ->assertSee('isn’t published yet');
});

test('comments wait for approval before they appear', function () {
    $story = makeStory();

    Livewire::test(StoryShow::class, ['story' => $story])
        ->set('comment', 'This took me straight back to my mum’s kitchen.')
        ->set('commentName', 'Chioma')
        ->set('commentEmail', 'Chioma@Example.com')
        ->call('postComment')
        ->assertHasNoErrors()
        ->assertSet('commentSent', true)
        ->assertSet('comment', '')
        ->assertSee('once our team has approved it')
        ->assertDontSee('straight back to my mum');

    $comment = StoryComment::sole();
    expect($comment->status)->toBe(StoryComment::STATUS_PENDING)
        ->and($comment->email)->toBe('chioma@example.com');

    $comment->update(['status' => StoryComment::STATUS_APPROVED]);

    $this->get(route('stories.show', $story))->assertSee('straight back to my mum')->assertSee('1 Comment');
});

test('comment validation, the spam trap and turned-off comments', function () {
    $story = makeStory();

    Livewire::test(StoryShow::class, ['story' => $story])
        ->call('postComment')
        ->assertHasErrors(['comment', 'commentName', 'commentEmail'])
        ->set('comment', str_repeat('a', StoryComment::MAX_LENGTH + 1))
        ->call('postComment')
        ->assertHasErrors(['comment' => 'max']);

    // A bot filling the hidden field sees the thank-you but nothing is saved.
    Livewire::test(StoryShow::class, ['story' => $story])
        ->set('comment', 'Buy cheap stuff')->set('commentName', 'Bot')->set('commentEmail', 'bot@example.com')
        ->set('website', 'http://spam.example')
        ->call('postComment')
        ->assertSet('commentSent', true);

    $closed = makeStory(['allow_comments' => false]);
    Livewire::test(StoryShow::class, ['story' => $closed])
        ->assertDontSee('Leave a Reply')
        ->set('comment', 'Hello')->set('commentName', 'Ada')->set('commentEmail', 'ada@example.com')
        ->call('postComment');

    expect(StoryComment::count())->toBe(0);
});

test('the stories sidebar signs readers up for email', function () {
    Livewire::test(StoriesIndex::class)
        ->set('subscribeName', 'Ada')
        ->set('subscribeEmail', 'Ada@Example.com')
        ->call('subscribe')
        ->assertHasNoErrors()
        ->assertSee('You’re on the list');

    expect(Consent::where('email', 'ada@example.com')->where('channel', 'email')->where('source', 'food_stories')->exists())->toBeTrue();
});

test('staff can write, schedule and publish stories', function () {
    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    Livewire::test(StoriesManager::class)
        ->call('create', Story::TYPE_UPDATE)
        ->set('title', 'We’re Open Late This Friday')
        ->assertSet('slug', 'were-open-late-this-friday')
        ->set('excerpt', 'Extra hours for your weekend shop.')
        ->set('body', 'See you there.')
        ->set('status', Story::STATUS_PUBLISHED)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showEditor', false);

    $update = Story::sole();
    expect($update->type)->toBe(Story::TYPE_UPDATE)
        ->and($update->isPublished())->toBeTrue()
        ->and($update->is_trending)->toBeFalse();

    // Same title again: the web address must be changed.
    Livewire::test(StoriesManager::class)
        ->call('create')
        ->set('title', 'We’re Open Late This Friday')
        ->call('save')
        ->assertHasErrors(['slug' => 'unique']);

    // A future date keeps it off the store until then.
    Livewire::test(StoriesManager::class)
        ->call('create')
        ->set('title', 'Coming Next Week')
        ->set('status', Story::STATUS_PUBLISHED)
        ->set('publishedAt', now()->addWeek()->setTimezone(config('shop.timezone'))->format('Y-m-d\TH:i'))
        ->call('save');

    expect(Story::where('slug', 'coming-next-week')->sole()->isPublished())->toBeFalse();

    Livewire::test(StoriesManager::class)
        ->call('togglePublish', $update->id)
        ->call('confirmDelete', $update->id)
        ->call('delete');

    expect(Story::find($update->id))->toBeNull();
});

test('staff approve, hide and delete comments', function () {
    $story = makeStory();
    $comment = $story->comments()->create(['name' => 'Kofi', 'email' => 'kofi@example.com', 'body' => 'Lovely read', 'status' => StoryComment::STATUS_PENDING]);

    $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));

    Livewire::test(StoriesManager::class)
        ->set('tab', 'comments')
        ->assertSee('Lovely read')
        ->assertSee('1 to review')
        ->call('approveComment', $comment->id);

    expect($comment->refresh()->status)->toBe(StoryComment::STATUS_APPROVED);

    Livewire::test(StoriesManager::class)->call('rejectComment', $comment->id);
    expect($comment->refresh()->status)->toBe(StoryComment::STATUS_REJECTED);

    Livewire::test(StoriesManager::class)->call('deleteComment', $comment->id);
    expect(StoryComment::count())->toBe(0);
});

test('the admin stories page needs a staff login', function () {
    $this->get(route('admin.stories'))->assertRedirect();
});

test('demo stories seed once, and the kitchen page and menus link to them', function () {
    $this->seed(StorySeeder::class);
    $this->seed(StorySeeder::class);

    expect(Story::ofType(Story::TYPE_FOOD)->count())->toBe(6)
        ->and(Story::ofType(Story::TYPE_UPDATE)->count())->toBe(4)
        ->and(Story::where('is_trending', true)->count())->toBe(3);

    $this->get(route('stories.index'))->assertOk()->assertSee('The Scotch Bonnet: Heat With A Heart');
    $this->get(route('stories.show', 'why-jollof-rice-is-never-just-rice'))->assertOk()->assertSee('Every Pot Tells a Story');
    $this->get(route('updates.index'))->assertOk()->assertSee('Black Friday Deals Are Here!');

    $this->get(route('kitchen.index'))
        ->assertOk()
        ->assertSee(route('stories.index'), false)
        ->assertSee('The Scotch Bonnet: Heat With A Heart');
});
