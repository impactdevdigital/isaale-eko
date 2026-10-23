<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Live Orders Queue for store operations and fulfillment.
 * Supports filtering, order inspection, and progress tracking
 * from order placement through preparation, pickup, and completion.
 */
class OrdersQueue extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'active'; // 'active', 'new', 'preparing', 'ready', 'completed', 'cancelled', 'all'

    public string $fulfilmentFilter = ''; // '', 'pickup', 'delivery'

    public string $paymentFilter = ''; // '', 'paid', 'unpaid'

    public ?int $selectedOrderId = null;

    public bool $showCancelModal = false;

    public string $cancelReason = '';

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
    }

    public function closeDrawer(): void
    {
        $this->selectedOrderId = null;
        $this->showCancelModal = false;
        $this->cancelReason = '';
    }

    public function startPreparing(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => Order::FULFILMENT_PREPARING,
        ]);

        session()->flash('success', "Order #{$order->number} marked as Preparing.");
    }

    public function markAsReady(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
        $order->update([
            'fulfilment_status' => Order::FULFILMENT_READY,
        ]);

        $action = $order->fulfilment === 'pickup' ? 'Ready for Collection' : 'Ready for Delivery';
        session()->flash('success', "Order #{$order->number} is now {$action}.");
    }

    public function markAsCompleted(int $orderId): void
    {
        $order = Order::findOrFail($orderId);
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

    public function openCancelModal(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $this->showCancelModal = true;
    }

    public function confirmCancel(): void
    {
        if (! $this->selectedOrderId) {
            return;
        }

        $order = Order::findOrFail($this->selectedOrderId);
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        $this->showCancelModal = false;
        session()->flash('warning', "Order #{$order->number} was marked as Cancelled.");
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
                    'completed' => $q->where('fulfilment_status', Order::FULFILMENT_COMPLETED),
                    'cancelled' => $q->where('status', Order::STATUS_CANCELLED),
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
            'completed_today' => Order::where('fulfilment_status', Order::FULFILMENT_COMPLETED)->whereDate('updated_at', Carbon::today())->count(),
        ];

        $selectedOrder = $this->selectedOrderId
            ? Order::with(['items.product', 'user'])->find($this->selectedOrderId)
            : null;

        return view('livewire.admin.orders-queue', [
            'orders' => $query->latest('placed_at')->paginate(15),
            'counts' => $counts,
            'selectedOrder' => $selectedOrder,
        ])->layout('layouts.admin', ['title' => 'Orders Queue']);
    }
}
