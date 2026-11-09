<?php

namespace App\Livewire\Admin;

use App\Models\Story;
use App\Models\StoryComment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Food Stories & Brand Updates desk: write and publish posts, and approve
 * reader comments before they appear on the storefront.
 */
class StoriesManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    /** 'stories' | 'comments' */
    public string $tab = 'stories';

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    public string $commentFilter = StoryComment::STATUS_PENDING;

    // Editor
    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $type = Story::TYPE_FOOD;

    public string $title = '';

    public string $slug = '';

    public string $excerpt = '';

    public string $body = '';

    public string $coverImage = '';

    public $coverUpload = null;

    public string $authorName = '';

    public ?int $readMinutes = null;

    public bool $isTrending = false;

    public bool $allowComments = true;

    public string $status = Story::STATUS_DRAFT;

    /** Shop-local date and time, as typed into the datetime-local field. */
    public string $publishedAt = '';

    public ?int $deletingId = null;

    protected $queryString = [
        'tab' => ['except' => 'stories'],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updating(string $name): void
    {
        if (in_array($name, ['search', 'typeFilter', 'statusFilter', 'commentFilter', 'tab'], true)) {
            $this->resetPage();
        }
    }

    public function create(string $type = Story::TYPE_FOOD): void
    {
        $this->resetEditor();
        $this->type = array_key_exists($type, Story::TYPES) ? $type : Story::TYPE_FOOD;
        $this->allowComments = $this->type === Story::TYPE_FOOD;
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $story = Story::findOrFail($id);

        $this->resetEditor();
        $this->editingId = $story->id;
        $this->type = $story->type;
        $this->title = $story->title;
        $this->slug = $story->slug;
        $this->excerpt = (string) $story->excerpt;
        $this->body = (string) $story->body;
        $this->coverImage = (string) $story->cover_image;
        $this->authorName = (string) $story->author_name;
        $this->readMinutes = $story->read_minutes;
        $this->isTrending = $story->is_trending;
        $this->allowComments = $story->allow_comments;
        $this->status = $story->status;
        $this->publishedAt = $story->published_at?->setTimezone(config('shop.timezone'))->format('Y-m-d\TH:i') ?? '';
        $this->showEditor = true;
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->resetEditor();
    }

    public function updatedTitle(): void
    {
        // Suggest a web address for new posts; existing links stay as they are.
        if (! $this->editingId) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function save(): void
    {
        $this->slug = Str::slug($this->slug ?: $this->title);

        $this->validate([
            'type' => ['required', Rule::in(array_keys(Story::TYPES))],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:180', Rule::unique('stories', 'slug')->ignore($this->editingId)],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string', 'max:60000'],
            'coverImage' => ['nullable', 'string', 'max:255'],
            'coverUpload' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'authorName' => ['nullable', 'string', 'max:120'],
            'readMinutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'status' => ['required', Rule::in([Story::STATUS_DRAFT, Story::STATUS_PUBLISHED])],
            'publishedAt' => ['nullable', 'date'],
        ], [
            'slug.unique' => 'Another post already uses this web address. Change it slightly.',
        ]);

        $cover = $this->coverImage ?: null;

        if ($this->coverUpload) {
            $filename = 'story-'.$this->slug.'-'.time().'.'.($this->coverUpload->getClientOriginalExtension() ?: 'jpg');
            $this->coverUpload->storeAs('stories/uploads', $filename, 'public');
            $cover = 'storage/stories/uploads/'.$filename;
        }

        $publishedAt = $this->publishedAt
            ? CarbonImmutable::parse($this->publishedAt, config('shop.timezone'))->utc()
            : null;

        // Publishing without a date means "now".
        if ($this->status === Story::STATUS_PUBLISHED && ! $publishedAt) {
            $publishedAt = now();
        }

        $data = [
            'type' => $this->type,
            'title' => trim($this->title),
            'slug' => $this->slug,
            'excerpt' => trim($this->excerpt) ?: null,
            'body' => $this->body ?: null,
            'cover_image' => $cover,
            'author_name' => trim($this->authorName) ?: null,
            'read_minutes' => $this->readMinutes ?: null,
            'is_trending' => $this->type === Story::TYPE_FOOD && $this->isTrending,
            'allow_comments' => $this->allowComments,
            'status' => $this->status,
            'published_at' => $publishedAt,
        ];

        $story = $this->editingId
            ? tap(Story::findOrFail($this->editingId))->update($data)
            : Story::create($data);

        session()->flash('success', "Saved “{$story->title}”.".($story->isPublished() ? ' It’s live on the store.' : ''));
        $this->closeEditor();
    }

    public function togglePublish(int $id): void
    {
        $story = Story::findOrFail($id);

        $story->update($story->status === Story::STATUS_PUBLISHED
            ? ['status' => Story::STATUS_DRAFT]
            : ['status' => Story::STATUS_PUBLISHED, 'published_at' => $story->published_at ?? now()]);

        session()->flash('success', $story->status === Story::STATUS_PUBLISHED ? "“{$story->title}” is published." : "“{$story->title}” is back to draft and hidden from the store.");
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if ($story = Story::find($this->deletingId)) {
            $story->delete();
            session()->flash('success', "Deleted “{$story->title}” and its comments.");
        }

        $this->deletingId = null;
    }

    public function approveComment(int $id): void
    {
        StoryComment::whereKey($id)->update(['status' => StoryComment::STATUS_APPROVED, 'approved_at' => now()]);
    }

    public function rejectComment(int $id): void
    {
        StoryComment::whereKey($id)->update(['status' => StoryComment::STATUS_REJECTED, 'approved_at' => null]);
    }

    public function deleteComment(int $id): void
    {
        StoryComment::whereKey($id)->delete();
    }

    private function resetEditor(): void
    {
        $this->reset('editingId', 'type', 'title', 'slug', 'excerpt', 'body', 'coverImage', 'coverUpload', 'authorName', 'readMinutes', 'isTrending', 'allowComments', 'status', 'publishedAt');
        $this->resetErrorBag();
    }

    public function render()
    {
        $stories = $this->tab === 'stories'
            ? Story::query()
                ->withCount(['comments as pending_comments_count' => fn ($q) => $q->where('status', StoryComment::STATUS_PENDING)])
                ->when(trim($this->search), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest('published_at')->latest('id')
                ->paginate(15)
            : null;

        $comments = $this->tab === 'comments'
            ? StoryComment::with('story')
                ->when($this->commentFilter, fn ($q) => $q->where('status', $this->commentFilter))
                ->latest()
                ->paginate(20)
            : null;

        return view('livewire.admin.stories-manager', [
            'stories' => $stories,
            'comments' => $comments,
            'pendingCount' => StoryComment::where('status', StoryComment::STATUS_PENDING)->count(),
        ])->layout('layouts.admin', ['header' => 'Stories & Updates']);
    }
}
