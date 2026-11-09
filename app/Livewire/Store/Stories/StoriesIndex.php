<?php

namespace App\Livewire\Store\Stories;

use App\Livewire\Store\Stories\Concerns\SubscribesToStories;
use App\Models\Story;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Food Stories listing (Figma 07.03): big photo cards, trending sidebar, newsletter box.
 */
#[Layout('layouts::store')]
#[Title('Food Stories')]
class StoriesIndex extends Component
{
    use SubscribesToStories;
    use WithPagination;

    public const PER_PAGE = 3;

    public function render()
    {
        return view('livewire.store.stories.stories-index', [
            'stories' => Story::published()->ofType(Story::TYPE_FOOD)->latest('published_at')->paginate(self::PER_PAGE),
            'trending' => StoryShow::trending(),
        ]);
    }
}
