<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use App\Models\ProductCollection;
use App\Models\Promotion;
use App\Services\Promotions\PromotionEngine;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Promotions from templates: choose a template, fill the required fields,
 * preview who is eligible and an example price, then activate. Each one
 * ends by itself on its end date.
 */
class PromotionsManager extends Component
{
    /** list | template | form | preview */
    public string $step = 'list';

    public ?int $editingId = null;

    public string $template = '';

    public string $name = '';

    public string $objective = 'discovery';

    public string $audience = 'everyone';

    public string $discountType = 'percent';

    public string $discountValue = '';

    /** @var array<int, int> */
    public array $productIds = [];

    public ?int $collectionId = null;

    public string $productSearch = '';

    public string $minSubtotal = '';

    public string $perCustomerLimit = '';

    public string $maxRedemptions = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public function create(): void
    {
        $this->resetForm();
        $this->step = 'template';
    }

    public function chooseTemplate(string $template): void
    {
        abort_unless(isset(Promotion::TEMPLATES[$template]), 404);

        $this->template = $template;
        $now = CarbonImmutable::now(config('shop.timezone'));
        $this->startsAt = $now->format('Y-m-d\TH:i');
        $this->endsAt = $now->addDays(14)->setTime(23, 59)->format('Y-m-d\TH:i');

        if ($template === Promotion::TEMPLATE_FIRST_ORDER) {
            $this->objective = 'acquisition';
            $this->audience = 'first_time';
            $this->discountType = 'fixed';
            $this->perCustomerLimit = '1';
        }

        $this->step = 'form';
    }

    public function edit(int $id): void
    {
        $promotion = Promotion::with('products')->findOrFail($id);
        abort_unless($promotion->status === 'draft', 403);

        $tz = config('shop.timezone');
        $this->editingId = $promotion->id;
        $this->template = $promotion->template;
        $this->name = $promotion->name;
        $this->objective = $promotion->objective;
        $this->audience = $promotion->audience;
        $this->discountType = $promotion->discount_type;
        $this->discountValue = $promotion->discount_type === 'percent' ? (string) $promotion->discount_value : number_format($promotion->discount_value / 100, 2, '.', '');
        $this->productIds = $promotion->products->modelKeys();
        $this->collectionId = $promotion->collection_id;
        $this->minSubtotal = $promotion->min_subtotal_cents ? number_format($promotion->min_subtotal_cents / 100, 2, '.', '') : '';
        $this->perCustomerLimit = (string) ($promotion->per_customer_limit ?? '');
        $this->maxRedemptions = (string) ($promotion->max_redemptions ?? '');
        $this->startsAt = $promotion->starts_at->timezone($tz)->format('Y-m-d\TH:i');
        $this->endsAt = $promotion->ends_at->timezone($tz)->format('Y-m-d\TH:i');
        $this->step = 'form';
    }

    public function addProduct(int $id): void
    {
        if (Product::whereKey($id)->exists() && ! in_array($id, $this->productIds, true)) {
            $this->productIds[] = $id;
        }

        $this->productSearch = '';
    }

    public function removeProduct(int $id): void
    {
        $this->productIds = array_values(array_diff($this->productIds, [$id]));
    }

    public function preview(): void
    {
        $this->validate($this->rules(), [
            'productIds.required_without' => 'Choose at least one product or a collection.',
        ], [
            'discountValue' => 'discount',
            'endsAt' => 'end date',
            'startsAt' => 'start date',
        ]);

        $this->step = 'preview';
    }

    public function back(): void
    {
        $this->step = $this->step === 'preview' ? 'form' : 'list';
    }

    public function save(bool $activate): void
    {
        $this->validate($this->rules());

        $tz = config('shop.timezone');
        $firstOrder = $this->template === Promotion::TEMPLATE_FIRST_ORDER;

        $promotion = Promotion::updateOrCreate(['id' => $this->editingId], [
            'name' => trim($this->name),
            'template' => $this->template,
            'objective' => $this->objective,
            'audience' => $firstOrder ? 'first_time' : $this->audience,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountCents(),
            'collection_id' => $firstOrder ? null : $this->collectionId,
            'min_subtotal_cents' => $this->minSubtotal !== '' ? (int) round((float) $this->minSubtotal * 100) : null,
            'per_customer_limit' => $this->perCustomerLimit !== '' ? (int) $this->perCustomerLimit : null,
            'max_redemptions' => $this->maxRedemptions !== '' ? (int) $this->maxRedemptions : null,
            'status' => $activate ? 'active' : 'draft',
            'starts_at' => CarbonImmutable::parse($this->startsAt, $tz)->utc(),
            'ends_at' => CarbonImmutable::parse($this->endsAt, $tz)->utc(),
            'created_by' => $this->editingId ? Promotion::find($this->editingId)?->created_by : auth()->id(),
        ]);

        $promotion->products()->sync($firstOrder ? [] : $this->productIds);

        session()->flash('success', $activate
            ? "“{$promotion->name}” is active. It ".($promotion->starts_at->isFuture() ? 'starts '.$promotion->starts_at->timezone($tz)->format('j M, g:i A').' and ' : '').'ends '.$promotion->ends_at->timezone($tz)->format('j M, g:i A').'.'
            : "Saved “{$promotion->name}” as a draft.");

        $this->resetForm();
    }

    public function pause(int $id): void
    {
        Promotion::whereKey($id)->where('status', 'active')->update(['status' => 'paused']);
    }

    public function resume(int $id): void
    {
        Promotion::whereKey($id)->where('status', 'paused')->where('ends_at', '>', now())->update(['status' => 'active']);
    }

    public function endNow(int $id): void
    {
        Promotion::whereKey($id)->where('ends_at', '>', now())->update(['ends_at' => now()]);
    }

    public function delete(int $id): void
    {
        // Only drafts: anything that ran stays for its history and redemptions.
        Promotion::whereKey($id)->where('status', 'draft')->delete();
    }

    private function rules(): array
    {
        $itemDiscount = $this->template === Promotion::TEMPLATE_ITEM_DISCOUNT;

        return [
            'template' => ['required', Rule::in(array_keys(Promotion::TEMPLATES))],
            'name' => ['required', 'string', 'max:80'],
            'objective' => ['required', Rule::in(array_keys(Promotion::OBJECTIVES))],
            'audience' => ['required', Rule::in(array_keys(Promotion::AUDIENCES))],
            'discountType' => ['required', Rule::in(['percent', 'fixed'])],
            'discountValue' => $this->discountType === 'percent'
                ? ['required', 'integer', 'min:1', 'max:90']
                : ['required', 'numeric', 'min:0.5', 'max:500'],
            'productIds' => $itemDiscount ? ['array', 'required_without:collectionId'] : ['array'],
            'productIds.*' => ['integer', 'exists:products,id'],
            'collectionId' => ['nullable', 'integer', 'exists:collections,id'],
            'minSubtotal' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'perCustomerLimit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'maxRedemptions' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after:startsAt', 'after:now'],
        ];
    }

    private function discountCents(): int
    {
        return $this->discountType === 'percent' ? (int) $this->discountValue : (int) round((float) $this->discountValue * 100);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'template', 'name', 'objective', 'audience', 'discountType', 'discountValue', 'productIds', 'collectionId', 'productSearch', 'minSubtotal', 'perCustomerLimit', 'maxRedemptions', 'startsAt', 'endsAt']);
        $this->resetErrorBag();
        $this->step = 'list';
    }

    /**
     * Who is eligible and what a shopper would pay, for the preview step.
     */
    private function previewData(): array
    {
        $engine = app(PromotionEngine::class);
        $audience = $this->template === Promotion::TEMPLATE_FIRST_ORDER ? 'first_time' : $this->audience;
        $draft = new Promotion(['discount_type' => $this->discountType, 'discount_value' => $this->discountCents()]);

        if ($this->template === Promotion::TEMPLATE_ITEM_DISCOUNT) {
            $ids = $this->productIds ?: ProductCollection::find($this->collectionId)?->products()->pluck('products.id')->all() ?? [];
            $product = Product::with('defaultVariation')->whereIn('id', $ids)->first();
            $before = $product?->defaultVariation?->price_cents ?? 0;
            $example = $product ? ['label' => "{$product->name} ({$product->defaultVariation?->name})", 'before' => $before, 'after' => $before - $draft->unitDiscount($before)] : null;
        } else {
            $before = max(5000, $this->minSubtotal !== '' ? (int) round((float) $this->minSubtotal * 100) : 0);
            $example = ['label' => 'A first order of '.money($before), 'before' => $before, 'after' => $before - $draft->basketDiscount($before)];
        }

        return [
            'eligible' => $engine->audienceSize($audience),
            'audienceLabel' => Promotion::AUDIENCES[$audience],
            'example' => $example,
            'productCount' => $this->template === Promotion::TEMPLATE_ITEM_DISCOUNT ? count(array_unique(array_merge($this->productIds, ProductCollection::find($this->collectionId)?->products()->pluck('products.id')->all() ?? []))) : null,
        ];
    }

    public function render()
    {
        return view('livewire.admin.promotions-manager', [
            'promotions' => $this->step === 'list'
                ? Promotion::withCount(['redemptions as redeemed' => fn ($query) => $query->whereIn('order_id', fn ($q) => $q->select('id')->from('orders')->whereNotIn('status', ['payment_failed', 'cancelled']))])
                    ->latest()
                    ->get()
                : collect(),
            'chosenProducts' => Product::whereIn('id', $this->productIds)->orderBy('name')->get(['id', 'name']),
            'searchResults' => strlen(trim($this->productSearch)) >= 2
                ? Product::where('name', 'like', '%'.trim($this->productSearch).'%')->whereNotIn('id', $this->productIds)->orderBy('name')->take(8)->get(['id', 'name'])
                : collect(),
            'collections' => ProductCollection::orderBy('name')->get(['id', 'name']),
            'preview' => $this->step === 'preview' ? $this->previewData() : null,
        ])->layout('layouts.admin', ['header' => 'Promotions']);
    }
}
