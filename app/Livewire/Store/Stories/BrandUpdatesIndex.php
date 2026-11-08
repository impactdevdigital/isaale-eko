<?php

namespace App\Livewire\Store\Stories;

use App\Models\Story;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Brand Updates listing (Figma 07.05): store news, holiday hours, new arrivals, offers.
 */
#[Layout('layouts::store')]
#[Title('Brand Updates')]
class BrandUpdatesIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 4;

    public function render()
    {
        return view('livewire.store.stories.brand-updates-index', [
            'updates' => Story::published()->ofType(Story::TYPE_UPDATE)->latest('published_at')->paginate(self::PER_PAGE),
        ]);
    }
}
