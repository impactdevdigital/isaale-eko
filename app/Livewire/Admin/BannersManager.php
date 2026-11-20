<?php

namespace App\Livewire\Admin;

use App\Models\Banner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Homepage banners: the two carousels on the homepage. Staff add, edit,
 * reorder, schedule and hide slides; changes show on the store at once.
 */
class BannersManager extends Component
{
    use WithFileUploads;

    /**
     * Background colours shown behind the photo while it loads and around
     * the text. Kept here so Tailwind builds these classes.
     */
    public const TINTS = [
        'bg-trend-60' => 'Purple',
        'bg-sale-50' => 'Orange',
        'bg-stock-50' => 'Green',
        'bg-brand-yellow-90' => 'Brown',
        'bg-ink' => 'Black',
        'bg-[#F3E3CC]' => 'Cream',
    ];

    public bool $showEditor = false;

    public ?int $editingId = null;

    public string $placement = 'hero';

    public string $eyebrow = '';

    public string $eyebrowFr = '';

    public string $title = '';

    public string $titleFr = '';

    public string $body = '';

    public string $bodyFr = '';

    public string $ctaLabel = '';

    public string $ctaLabelFr = '';

    public string $ctaUrl = '';

    public string $image = '';

    public $imageUpload = null;

    public string $tint = 'bg-trend-60';

    public string $style = 'light';

    public bool $isActive = true;

    /** Shop-local date and time, as typed into the datetime-local fields. */
    public string $startsAt = '';

    public string $endsAt = '';

    public ?int $deletingId = null;

    public function create(string $placement = 'hero'): void
    {
        $this->resetEditor();
        $this->placement = array_key_exists($placement, Banner::PLACEMENTS) ? $placement : 'hero';
        $this->showEditor = true;
    }

    public function edit(int $id): void
    {
        $banner = Banner::findOrFail($id);

        $this->resetEditor();
        $this->editingId = $banner->id;
        $this->placement = $banner->placement;
        $this->eyebrow = (string) $banner->eyebrow;
        $this->eyebrowFr = (string) $banner->eyebrow_fr;
        $this->title = $banner->title;
        $this->titleFr = (string) $banner->title_fr;
        $this->body = (string) $banner->body;
        $this->bodyFr = (string) $banner->body_fr;
        $this->ctaLabel = (string) $banner->cta_label;
        $this->ctaLabelFr = (string) $banner->cta_label_fr;
        $this->ctaUrl = (string) $banner->cta_url;
        $this->image = $banner->image;
        $this->tint = $banner->tint;
        $this->style = $banner->style ?: 'light';
        $this->isActive = $banner->is_active;
        $this->startsAt = $this->local($banner->starts_at);
        $this->endsAt = $this->local($banner->ends_at);
        $this->showEditor = true;
    }

    public function closeEditor(): void
    {
        $this->showEditor = false;
        $this->resetEditor();
    }

    public function save(): void
    {
        $this->ctaUrl = trim($this->ctaUrl);

        $this->validate([
            'placement' => ['required', Rule::in(array_keys(Banner::PLACEMENTS))],
            'eyebrow' => ['nullable', 'string', 'max:60'],
            'eyebrowFr' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:60'],
            'titleFr' => ['nullable', 'string', 'max:60'],
            'body' => ['nullable', 'string', 'max:160'],
            'bodyFr' => ['nullable', 'string', 'max:160'],
            'ctaLabel' => ['nullable', 'string', 'max:30', 'required_with:ctaUrl'],
            'ctaLabelFr' => ['nullable', 'string', 'max:30'],
            // A page on this site (/shop, /recipes/jollof) or a full https:// address.
            'ctaUrl' => ['nullable', 'string', 'max:255', 'required_with:ctaLabel', 'regex:#^(/|https?://)#'],
            'imageUpload' => [$this->image ? 'nullable' : 'required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'tint' => ['required', Rule::in(array_keys(self::TINTS))],
            'style' => ['required', Rule::in(array_keys(Banner::STYLES))],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after:startsAt'],
        ], [
            'title.required' => 'Give the banner a headline.',
            'imageUpload.required' => 'Add a photo for the banner.',
            'ctaUrl.regex' => 'Use a page on this site starting with / (e.g. /shop) or a full address starting with https://.',
            'ctaUrl.required_with' => 'Add where the button should go.',
            'ctaLabel.required_with' => 'Add the button text.',
            'endsAt.after' => 'The end must be after the start.',
        ]);

        $image = $this->image;

        if ($this->imageUpload) {
            $filename = 'banner-'.Str::slug($this->title).'-'.time().'.'.($this->imageUpload->getClientOriginalExtension() ?: 'jpg');
            $this->imageUpload->storeAs('banners', $filename, 'public');
            $image = 'storage/banners/'.$filename;
        }

        $data = [
            'placement' => $this->placement,
            'eyebrow' => trim($this->eyebrow) ?: null,
            'eyebrow_fr' => trim($this->eyebrowFr) ?: null,
            'title' => trim($this->title),
            'title_fr' => trim($this->titleFr) ?: null,
            'body' => trim($this->body) ?: null,
            'body_fr' => trim($this->bodyFr) ?: null,
            'cta_label' => trim($this->ctaLabel) ?: null,
            'cta_label_fr' => trim($this->ctaLabelFr) ?: null,
            'cta_url' => $this->ctaUrl ?: null,
            'image' => $image,
            'tint' => $this->tint,
            'style' => $this->style,
            'is_active' => $this->isActive,
            'starts_at' => $this->utc($this->startsAt),
            'ends_at' => $this->utc($this->endsAt),
        ];

        if ($this->editingId) {
            $banner = tap(Banner::findOrFail($this->editingId))->update($data);
        } else {
            $data['sort_order'] = (int) Banner::where('placement', $this->placement)->max('sort_order') + 1;
            $banner = Banner::create($data);
        }

        session()->flash('success', "Saved “{$banner->title}”.".($banner->isLive() ? ' It’s showing on the homepage.' : ''));
        $this->closeEditor();
    }

    public function toggleActive(int $id): void
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['is_active' => ! $banner->is_active]);
    }

    /** Swap with the neighbour above (-1) or below (+1) in the same carousel. */
    public function move(int $id, int $direction): void
    {
        $banner = Banner::findOrFail($id);
        $siblings = Banner::where('placement', $banner->placement)->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $siblings->search(fn ($b) => $b->id === $banner->id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($target < 0 || $target >= $siblings->count()) {
            return;
        }

        $order = $siblings->pluck('id')->all();
        [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

        foreach ($order as $position => $bannerId) {
            Banner::whereKey($bannerId)->update(['sort_order' => $position]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
    }

    public function delete(): void
    {
        if ($banner = Banner::find($this->deletingId)) {
            session()->flash('success', "Deleted “{$banner->title}”.");
            $banner->delete();
        }

        $this->deletingId = null;
    }

    private function resetEditor(): void
    {
        $this->reset('editingId', 'placement', 'eyebrow', 'eyebrowFr', 'title', 'titleFr', 'body', 'bodyFr', 'ctaLabel', 'ctaLabelFr', 'ctaUrl', 'image', 'imageUpload', 'tint', 'style', 'isActive', 'startsAt', 'endsAt');
        $this->resetErrorBag();
    }

    private function local($time): string
    {
        return $time?->setTimezone(config('shop.timezone'))->format('Y-m-d\TH:i') ?? '';
    }

    private function utc(string $value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::parse($value, config('shop.timezone'))->utc() : null;
    }

    public function render()
    {
        $banners = Banner::orderBy('sort_order')->orderBy('id')->get()->groupBy('placement');

        return view('livewire.admin.banners-manager', [
            'groups' => collect(Banner::PLACEMENTS)->map(fn ($label, $key) => ['label' => $label, 'banners' => $banners->get($key, collect())]),
            'previewImage' => $this->imageUpload && method_exists($this->imageUpload, 'temporaryUrl')
                ? rescue(fn () => $this->imageUpload->temporaryUrl(), null, false)
                : ($this->image ? (Str::startsWith($this->image, ['http://', 'https://']) ? $this->image : asset($this->image)) : null),
        ])->layout('layouts.admin', ['header' => 'Homepage Banners']);
    }
}
