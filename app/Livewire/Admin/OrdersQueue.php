<?php

namespace App\Livewire\Admin;

use App\Actions\Orders\CancelUncollectedOrder;
use App\Actions\Orders\ExpireSubstitutions;
use App\Actions\Orders\RequestSubstitution;
use App\Actions\Orders\RescheduleOrder;
use App\Actions\Orders\RespondToSubstitution;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariation;
use App\Models\Setting;
use App\Models\SubstitutionRequest;
use App\Services\Store\StoreHours;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Live Orders Queue for store operations and fulfillment.
 * Supports filtering, order inspection, and progress tracking
 * from order placement through preparation, pickup, and completion.
 * Implements Part E9: Picking screen & no silent substitutions,
 * and Part E6b: Uncollected orders desk & pickup rescheduling.
 */
class OrdersQueue extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active'; // 'active', 'new', 'preparing', 'ready', 'uncollected', 'completed', 'cancelled', 'all'

    public string $fulfilmentFilter = ''; // '', 'pickup', 'delivery'

    public string $paymentFilter = ''; // '', 'paid', 'unpaid'

    public ?int $selectedOrderId = null;

    public bool $showCancelModal = false;

    public string $cancelReason = 'not_collected';

    public int $cancelRefundPercent = 100;

    // Reschedule Modal State (Part E6b)
    public bool $showRescheduleModal = false;

    public string $rescheduleDate = '';

    public string $rescheduleSlot = '';

    // Substitution Picker Modal State (Part E9)
    public bool $showSubstitutionModal = false;

    public ?int $substitutionItemId = null;

    public ?int $selectedSubstituteVariationId = null;

    public string $substituteSearch = '';

    public string $substitutionNote = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'active'],
        'fulfilmentFilter' => ['except' => ''],
        'paymentFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFulfilmentFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentFilter(): void
    {
        $this->resetPage();
    }

    public function viewOrder(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->showSubstitutionModal = false;
    }

    public function closeDrawer(): void
    {
        $this->selectedOrderId = null;
        $this->showCancelModal = false;
        $this->cancelReason = 'not_collected';
        $this->showSubstitutionModal = false;
        $this->showRescheduleModal = false;
    }

    public function startPreparing(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => Order::FULFILMENT_PREPARING,
        ]);

        session()->flash('success', "Order #{$order->number} marked as Preparing.");
    }

    public function markAsReady(int $orderId, ExpireSubstitutions $expire): void
    {
        $order = Order::findOrFail($orderId);

        // Stage Gate Rule (Part E9): auto-refund any unresolved substitution requests before marking ready
        $expiredCount = $expire->forOrder($order);
        if ($expiredCount > 0) {
            session()->flash('warning', "{$expiredCount} unanswered substitution request(s) were automatically refunded to fulfill the stage deadline.");
        }

        $order->update([
            'fulfilment_status' => Order::FULFILMENT_READY,
        ]);

        $action = $order->fulfilment === 'pickup' ? 'Ready for Collection' : 'Ready for Delivery';
        session()->flash('success', "Order #{$order->number} is now {$action}.");
    }

    public function markAsCompleted(int $orderId, ExpireSubstitutions $expire): void
    {
        $order = Order::findOrFail($orderId);

        // Stage Gate Rule (Part E9): auto-refund any unresolved substitution requests before completing
        $expiredCount = $expire->forOrder($order);
        if ($expiredCount > 0) {
            session()->flash('warning', "{$expiredCount} unanswered substitution request(s) were automatically refunded to fulfill the stage deadline.");
        }

        $updates = [
            'fulfilment_status' => Order::FULFILMENT_COMPLETED,
        ];

        // If it was pay at pickup, fulfilling confirms counter payment
        if ($order->status === Order::STATUS_PLACED) {
            $updates['status'] = Order::STATUS_PAID;
            $updates['paid_at'] = now();
        }

        $order->update($updates);

        $action = $order->fulfilment === 'pickup' ? 'Collected' : 'Delivered';
        session()->flash('success', "Order #{$order->number} has been completed ({$action}).");
    }

    // -------------------------------------------------------------
    // Part E6b: Uncollected Orders & Reschedule Desk Actions
    // -------------------------------------------------------------

    public function openRescheduleModal(int $orderId, StoreHours $storeHours): void
    {
        $this->selectedOrderId = $orderId;
        $days = $storeHours->pickableDays();
        if ($days->isNotEmpty()) {
            $this->rescheduleDate = $days->first()->toDateString();
            $slots = $storeHours->slotsFor($days->first());
            $this->rescheduleSlot = $slots->isNotEmpty() ? $slots->first()->format('H:i') : '';
        }
        $this->showRescheduleModal = true;
    }

    public function updatedRescheduleDate(StoreHours $storeHours): void
    {
        if ($this->rescheduleDate) {
            $slots = $storeHours->slotsFor(Carbon::parse($this->rescheduleDate));
            $this->rescheduleSlot = $slots->isNotEmpty() ? $slots->first()->format('H:i') : '';
        }
    }

    public function confirmReschedule(RescheduleOrder $rescheduleAction): void
    {
        if (! $this->selectedOrderId || ! $this->rescheduleDate || ! $this->rescheduleSlot) {
            return;
        }

        $order = Order::findOrFail($this->selectedOrderId);

        try {
            $dt = Carbon::parse("{$this->rescheduleDate} {$this->rescheduleSlot}", config('shop.timezone', 'America/Moncton'));
            $rescheduleAction->handle($order, $dt, 'staff');

            $this->showRescheduleModal = false;
            session()->flash('success', "Order #{$order->number} pickup rescheduled to {$dt->format('M j, g:i A')}.");
        } catch (\Throwable $e) {
            session()->flash('warning', $e->getMessage());
        }
    }

    public function openCancelModal(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->cancelRefundPercent = (int) Setting::get('uncollected_refund_percent', 100);
        $this->cancelReason = 'not_collected';
        $this->showCancelModal = true;
    }

    public function confirmCancel(CancelUncollectedOrder $cancelUncollected): void
    {
        if (! $this->selectedOrderId) {
            return;
        }

        $order = Order::findOrFail($this->selectedOrderId);
        $cancelUncollected->handle($order, $this->cancelRefundPercent, $this->cancelReason ?: 'staff_cancelled');

        $this->showCancelModal = false;
        session()->flash('warning', "Order #{$order->number} was cancelled and processed per policy.");
    }

    // -------------------------------------------------------------
    // Part E9: Picking Screen & Substitution Desk Actions
    // -------------------------------------------------------------

    public function openSubstitutionModal(int $orderItemId): void
    {
        $this->substitutionItemId = $orderItemId;
        $this->selectedSubstituteVariationId = null;
        $this->substituteSearch = '';
        $this->substitutionNote = '';
        $this->showSubstitutionModal = true;
    }

    public function closeSubstitutionModal(): void
    {
        $this->showSubstitutionModal = false;
        $this->substitutionItemId = null;
        $this->selectedSubstituteVariationId = null;
        $this->substituteSearch = '';
        $this->substitutionNote = '';
    }

    public function createSubstitution(RequestSubstitution $requester): void
    {
        if (! $this->substitutionItemId) {
            return;
        }

        $item = OrderItem::findOrFail($this->substitutionItemId);
        $substitute = $this->selectedSubstituteVariationId
            ? ProductVariation::find($this->selectedSubstituteVariationId)
            : null;

        $requester->create($item, $substitute, auth()->user(), $this->substitutionNote ?: null);

        $this->closeSubstitutionModal();

        session()->flash('success', "Substitution initiated for {$item->name}. Customer phone: {$item->order->contact_phone}. Please call the customer first.");
    }

    public function logCall(int $requestId, bool $reached, RequestSubstitution $requester): void
    {
        $request = SubstitutionRequest::findOrFail($requestId);
        $requester->logCall($request, $reached, auth()->user());

        $status = $reached ? 'Customer Reached' : 'No Answer';
        session()->flash('info', "Phone call recorded ({$status}) for order #{$request->order->number}.");
    }

    public function recordCallDecision(int $requestId, string $decision, RespondToSubstitution $responder): void
    {
        $request = SubstitutionRequest::findOrFail($requestId);
        $responder->handle($request, $decision, 'staff');

        $action = $decision === 'approve' ? 'approved' : 'declined and refunded';
        session()->flash('success', "Customer phone decision recorded: {$action}.");
    }

    public function sendCustomerMessage(int $requestId, RequestSubstitution $requester): void
    {
        $request = SubstitutionRequest::findOrFail($requestId);
        $requester->sendMessage($request);

        session()->flash('success', "Substitution email sent to {$request->order->contact_email}. 15-minute countdown clock is running.");
    }

    public function cancelSubstitution(int $requestId): void
    {
        $request = SubstitutionRequest::findOrFail($requestId);
        $request->update([
            'status' => SubstitutionRequest::STATUS_CANCELLED,
            'resolved_at' => now(),
        ]);

        $request->item->update([
            'line_status' => OrderItem::STATUS_ACTIVE,
        ]);

        session()->flash('info', "Substitution request for {$request->item->name} was cancelled. Item returned to active picking.");
    }

    public function getAvailableSubstitutesProperty()
    {
        if (! $this->substitutionItemId) {
            return collect();
        }

        $item = OrderItem::with('product.category')->find($this->substitutionItemId);
        if (! $item) {
            return collect();
        }

        return ProductVariation::with('product')
            ->whereHas('product', function ($q) use ($item) {
                $q->where('status', 'active');
                if (trim($this->substituteSearch) !== '') {
                    $q->where('name', 'like', '%' . trim($this->substituteSearch) . '%');
                } elseif ($item->product?->category_id) {
                    $q->where('category_id', $item->product->category_id);
                }
            })
            ->where(fn ($q) => $q->whereNull('stock_quantity')->orWhere('stock_quantity', '>', 0))
            ->limit(12)
            ->get();
    }

    public function render()
    {
        $query = Order::with(['items.product', 'user'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('number', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('contact_phone', 'like', "%{$term}%")
                        ->orWhere('contact_email', 'like', "%{$term}%");
                });
            })
            ->when($this->fulfilmentFilter, fn ($q) => $q->where('fulfilment', $this->fulfilmentFilter))
            ->when($this->paymentFilter, function ($q) {
                if ($this->paymentFilter === 'paid') {
                    $q->where('status', Order::STATUS_PAID);
                } elseif ($this->paymentFilter === 'unpaid') {
                    $q->where(fn ($sub) => $sub->where('status', Order::STATUS_AWAITING_PAYMENT)
                        ->orWhere(fn ($p) => $p->where('status', Order::STATUS_PLACED)->where('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED)));
                }
            })
            ->when($this->statusFilter, function ($q) {
                match ($this->statusFilter) {
                    'active' => $q->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])
                        ->where(fn ($sub) => $sub->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED)),
                    'new' => $q->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->whereNull('fulfilment_status'),
                    'preparing' => $q->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->where('fulfilment_status', Order::FULFILMENT_PREPARING),
                    'ready' => $q->whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->where('fulfilment_status', Order::FULFILMENT_READY),
                    'uncollected' => $q->where('status', Order::STATUS_UNCOLLECTED),
                    'completed' => $q->where('fulfilment_status', Order::FULFILMENT_COMPLETED),
                    'cancelled' => $q->whereIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED]),
                    default => null, // 'all'
                };
            });

        $counts = [
            'active' => Order::whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])
                ->where(fn ($sub) => $sub->whereNull('fulfilment_status')->orWhere('fulfilment_status', '!=', Order::FULFILMENT_COMPLETED))
                ->count(),
            'new' => Order::whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->whereNull('fulfilment_status')->count(),
            'preparing' => Order::whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->where('fulfilment_status', Order::FULFILMENT_PREPARING)->count(),
            'ready' => Order::whereIn('status', [Order::STATUS_PLACED, Order::STATUS_PAID])->where('fulfilment_status', Order::FULFILMENT_READY)->count(),
            'uncollected' => Order::where('status', Order::STATUS_UNCOLLECTED)->count(),
            'completed_today' => Order::where('fulfilment_status', Order::FULFILMENT_COMPLETED)->whereDate('updated_at', Carbon::today())->count(),
        ];

        $selectedOrder = $this->selectedOrderId
            ? Order::with([
                'items.product',
                'items.variation',
                'items.activeSubstitutionRequest.substitute.product',
                'items.latestSubstitutionRequest.substitute.product',
                'user',
                'substitutionRequests.item',
                'substitutionRequests.substitute.product',
            ])->find($this->selectedOrderId)
            : null;

        $storeHours = app(StoreHours::class);
        $rescheduleDays = $this->showRescheduleModal ? $storeHours->pickableDays() : collect();
        $rescheduleSlots = ($this->showRescheduleModal && $this->rescheduleDate)
            ? $storeHours->slotsFor(Carbon::parse($this->rescheduleDate))
            : collect();

        return view('livewire.admin.orders-queue', [
            'orders' => $query->latest('placed_at')->paginate(15),
            'counts' => $counts,
            'selectedOrder' => $selectedOrder,
            'availableSubstitutes' => $this->availableSubstitutes,
            'rescheduleDays' => $rescheduleDays,
            'rescheduleSlots' => $rescheduleSlots,
        ])->layout('layouts.admin', ['title' => 'Orders Queue']);
    }
}
