<?php

namespace App\Livewire\Store\Stories;

use App\Livewire\Store\Stories\Concerns\SubscribesToStories;
use App\Models\Story;
use App\Models\StoryComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * A Food Story or Brand Update (Figma 07.04). Comments are held for staff approval.
 */
#[Layout('layouts::store')]
class StoryShow extends Component
{
    use SubscribesToStories;

    #[Locked]
    public Story $story;

    public string $comment = '';

    public string $commentName = '';

    public string $commentEmail = '';

    /** Spam trap: hidden from people, filled in by bots. */
    public string $website = '';

    public bool $commentSent = false;

    public function mount(Story $story): void
    {
        $type = request()->routeIs('updates.show') ? Story::TYPE_UPDATE : Story::TYPE_FOOD;

        // Drafts are only visible to staff previewing them.
        abort_unless($story->type === $type && ($story->isPublished() || Auth::user()?->is_admin), 404);

        $this->story = $story;

        if ($user = Auth::user()) {
            $this->commentName = (string) $user->name;
            $this->commentEmail = (string) $user->email;
        }
    }

    public function postComment(): void
    {
        if (! $this->story->allow_comments || ! $this->story->isPublished()) {
            return;
        }

        $this->validate([
            'comment' => ['required', 'string', 'min:2', 'max:'.StoryComment::MAX_LENGTH],
            'commentName' => ['required', 'string', 'max:120'],
            'commentEmail' => ['required', 'email:rfc', 'max:255'],
        ], [
            'comment.required' => 'Write your comment.',
            'comment.max' => 'Keep your comment under '.StoryComment::MAX_LENGTH.' characters.',
            'commentName.required' => 'Enter your name.',
            'commentEmail.required' => 'Enter your email address.',
            'commentEmail.email' => 'Enter a valid email address.',
        ]);

        $key = 'story-comment:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('comment', 'You’ve posted a few comments already. Try again in a few minutes.');

            return;
        }

        RateLimiter::hit($key, 600);

        // Bots that fill the hidden field get the same thank-you, but nothing is saved.
        if ($this->website === '') {
            $this->story->comments()->create([
                'user_id' => Auth::id(),
                'name' => trim($this->commentName),
                'email' => strtolower(trim($this->commentEmail)),
                'body' => trim($this->comment),
                'status' => StoryComment::STATUS_PENDING,
                'ip_address' => request()->ip(),
            ]);
        }

        $this->reset('comment');
        $this->commentSent = true;
    }

    /**
     * Trending Food Stories for the sidebar, topped up with the newest ones.
     *
     * @return Collection<int, Story>
     */
    public static function trending(?int $exceptId = null, int $limit = 3): Collection
    {
        $base = fn () => Story::published()->ofType(Story::TYPE_FOOD)->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId));

        $trending = $base()->where('is_trending', true)->latest('published_at')->take($limit)->get();

        return $trending->concat(
            $base()->whereNotIn('id', $trending->modelKeys())->latest('published_at')->take($limit - $trending->count())->get()
        );
    }

    public function render()
    {
        return view('livewire.store.stories.story-show', [
            'comments' => $this->story->approvedComments()->get(),
            'trending' => self::trending($this->story->id),
        ])->title($this->story->title);
    }
}
