<?php

namespace App\Livewire\Admin;

use App\Actions\Issues\DeclineIssue;
use App\Actions\Issues\OfferResolutions;
use App\Actions\Issues\OpenOrderIssue;
use App\Actions\Issues\ResolveIssue;
use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\OrderIssueItem;
use App\Services\Orders\RefundCalculator;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class IssuesDesk extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'open'; // open | new | reviewing | awaiting_customer | resolved | declined | all

    public string $kindFilter = ''; // problem | return | safety

    public ?int $selectedIssueId = null;

    // Resolution Drawer / Modal
    public bool $showResolutionModal = false;

    public string $resolutionType = 'refund'; // refund | replacement | offer | decline

    public bool $includeDeliveryFee = false;

    public string $declineReasonKey = 'food_change_of_mind';

    public string $declineCustomNote = '';

    public array $offeredResolutions = ['refund', 'replacement'];

    public string $newStaffNote = '';

    // "Log a Case" Modal
    public bool $showLogCaseModal = false;

    public string $logCaseOrderNumber = '';

    public string $logCaseChannel = 'phone'; // phone | whatsapp | email | in_store

    public string $logCaseKind = 'problem';

    public string $logCaseDescription = '';

    public ?int $logCaseOrderId = null;

    public array $logCaseSelectedItems = []; // [item_id => ['selected' => bool, 'qty' => int, 'type' => string]]

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'open'],
        'kindFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingKindFilter(): void
    {
        $this->resetPage();
    }

    public function viewIssue(int $id): void
    {
        $this->selectedIssueId = $id;
        $this->showResolutionModal = false;
        $this->newStaffNote = '';

        $issue = OrderIssue::find($id);
        if ($issue && $issue->status === OrderIssue::STATUS_NEW) {
            $issue->update([
                'status' => OrderIssue::STATUS_REVIEWING,
                'first_response_at' => $issue->first_response_at ?? now(),
                'assigned_to' => auth()->id(),
            ]);

            $issue->events()->create([
                'type' => 'reviewing',
                'actor' => 'staff',
                'note' => 'Case opened and taken under active review by ' . (auth()->user()?->name ?? 'Staff'),
            ]);
        }
    }

    public function closeDrawer(): void
    {
        $this->selectedIssueId = null;
        $this->showResolutionModal = false;
        $this->newStaffNote = '';
    }

    public function openResolutionModal(string $type = 'refund'): void
    {
        $this->resolutionType = $type;
        $this->showResolutionModal = true;
    }

    public function closeResolutionModal(): void
    {
        $this->showResolutionModal = false;
    }

    public function executeResolution(
        ResolveIssue $resolver,
        DeclineIssue $decliner,
        OfferResolutions $offerer
    ): void {
        if (! $this->selectedIssueId) {
            return;
        }

        $issue = OrderIssue::findOrFail($this->selectedIssueId);
        $user = auth()->user();

        if ($this->resolutionType === 'refund') {
            $resolver->handle($issue, 'refund', $user, $this->includeDeliveryFee);
            session()->flash('success', "Refund issued successfully for claim {$issue->reference}.");
        } elseif ($this->resolutionType === 'replacement') {
            $resolver->handle($issue, 'replacement', $user);
            session()->flash('success', "Replacement order scheduled for claim {$issue->reference}.");
        } elseif ($this->resolutionType === 'offer') {
            $offerer->handle($issue, $this->offeredResolutions, $user);
            session()->flash('success', "Resolution options sent to customer for claim {$issue->reference}.");
        } elseif ($this->resolutionType === 'decline') {
            $decliner->handle($issue, $this->declineReasonKey, $this->declineCustomNote, $user);
            session()->flash('info', "Claim {$issue->reference} marked as declined per store policy.");
        }

        $this->closeResolutionModal();
    }

    public function addStaffNote(): void
    {
        if (! $this->selectedIssueId || trim($this->newStaffNote) === '') {
            return;
        }

        $issue = OrderIssue::findOrFail($this->selectedIssueId);
        $issue->events()->create([
            'type' => 'note_added',
            'actor' => 'staff',
            'note' => trim($this->newStaffNote),
        ]);

        $this->newStaffNote = '';
        session()->flash('success', 'Staff note recorded in case diary.');
    }

    // -------------------------------------------------------------------------
    // "Log a Case" on behalf of Phone / WhatsApp / Walk-in Customer
    // -------------------------------------------------------------------------

    public function openLogCaseModal(): void
    {
        $this->resetLogCaseForm();
        $this->showLogCaseModal = true;
    }

    public function closeLogCaseModal(): void
    {
        $this->showLogCaseModal = false;
        $this->resetLogCaseForm();
    }

    public function resetLogCaseForm(): void
    {
        $this->logCaseOrderNumber = '';
        $this->logCaseChannel = 'phone';
        $this->logCaseKind = 'problem';
        $this->logCaseDescription = '';
        $this->logCaseOrderId = null;
        $this->logCaseSelectedItems = [];
    }

    public function findOrderForLogCase(): void
    {
        $clean = trim($this->logCaseOrderNumber);
        if ($clean === '') {
            return;
        }

        $order = Order::with('items')->where('number', $clean)->first();
        if (! $order) {
            $this->addError('logCaseOrderNumber', "No order found matching '{$clean}'.");
            $this->logCaseOrderId = null;
            $this->logCaseSelectedItems = [];
            return;
        }

        $this->resetErrorBag('logCaseOrderNumber');
        $this->logCaseOrderId = $order->id;
        $this->logCaseSelectedItems = [];

        foreach ($order->items as $item) {
            $this->logCaseSelectedItems[$item->id] = [
                'selected' => false,
                'quantity' => $item->quantity,
                'issue_type' => OrderIssueItem::TYPE_DAMAGED,
            ];
        }
    }

    public function saveLogCase(OpenOrderIssue $opener): void
    {
        if (! $this->logCaseOrderId) {
            $this->addError('logCaseOrderNumber', 'Please enter a valid order number first.');
            return;
        }

        $order = Order::with('items')->findOrFail($this->logCaseOrderId);

        $lines = [];
        foreach ($this->logCaseSelectedItems as $itemId => $data) {
            if (! empty($data['selected'])) {
                $lines[] = [
                    'order_item_id' => $itemId,
                    'quantity' => (int) ($data['quantity'] ?? 1),
                    'issue_type' => $data['issue_type'] ?? OrderIssueItem::TYPE_DAMAGED,
                ];
            }
        }

        if (empty($lines)) {
            $this->addError('logCaseItems', 'Please select at least one affected item checkbox below.');
            return;
        }

        $issue = $opener->handle(
            $order,
            $lines,
            $this->logCaseDescription ?: 'Customer called in with claim.',
            [],
            $this->logCaseChannel,
            auth()->user(),
            'Staff manual intake',
            $this->logCaseKind
        );

        $this->closeLogCaseModal();
        $this->viewIssue($issue->id);
        session()->flash('success', "Customer case #{$issue->reference} created successfully.");
    }

    public function render(RefundCalculator $calculator)
    {
        $query = OrderIssue::with(['order', 'items.orderItem', 'user', 'photos', 'events'])
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('reference', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('order', fn ($o) => $o->where('number', 'like', "%{$term}%")->orWhere('contact_name', 'like', "%{$term}%")->orWhere('contact_phone', 'like', "%{$term}%"));
                });
            })
            ->when($this->kindFilter, fn ($q) => $q->where('kind', $this->kindFilter))
            ->when($this->statusFilter, function ($q) {
                match ($this->statusFilter) {
                    'open' => $q->whereIn('status', [OrderIssue::STATUS_NEW, OrderIssue::STATUS_REVIEWING, OrderIssue::STATUS_AWAITING_CUSTOMER, OrderIssue::STATUS_AWAITING_RETURN]),
                    'new' => $q->where('status', OrderIssue::STATUS_NEW),
                    'reviewing' => $q->where('status', OrderIssue::STATUS_REVIEWING),
                    'awaiting_customer' => $q->where('status', OrderIssue::STATUS_AWAITING_CUSTOMER),
                    'resolved' => $q->where('status', OrderIssue::STATUS_RESOLVED),
                    'declined' => $q->where('status', OrderIssue::STATUS_DECLINED),
                    default => null, // 'all'
                };
            });

        $counts = [
            'open' => OrderIssue::whereIn('status', [OrderIssue::STATUS_NEW, OrderIssue::STATUS_REVIEWING, OrderIssue::STATUS_AWAITING_CUSTOMER, OrderIssue::STATUS_AWAITING_RETURN])->count(),
            'new' => OrderIssue::where('status', OrderIssue::STATUS_NEW)->count(),
            'safety' => OrderIssue::where('kind', OrderIssue::KIND_SAFETY)->whereIn('status', [OrderIssue::STATUS_NEW, OrderIssue::STATUS_REVIEWING])->count(),
            'overdue' => OrderIssue::where('status', OrderIssue::STATUS_NEW)->whereNull('first_response_at')->where('due_at', '<=', now())->count(),
            'resolved_today' => OrderIssue::where('status', OrderIssue::STATUS_RESOLVED)->whereDate('resolved_at', Carbon::today())->count(),
        ];

        $selectedIssue = $this->selectedIssueId
            ? OrderIssue::with(['order.items', 'items.orderItem', 'user', 'photos', 'events', 'refunds'])->find($this->selectedIssueId)
            : null;

        $calculatedRefund = $selectedIssue
            ? $calculator->forIssue($selectedIssue, $this->includeDeliveryFee)
            : ['amount_cents' => 0, 'delivery_fee_cents' => 0];

        $logCaseOrder = $this->logCaseOrderId ? Order::with('items')->find($this->logCaseOrderId) : null;

        return view('livewire.admin.issues-desk', [
            'issues' => $query->latest('reported_at')->paginate(15),
            'counts' => $counts,
            'selectedIssue' => $selectedIssue,
            'calculatedRefund' => $calculatedRefund,
            'logCaseOrder' => $logCaseOrder,
        ])->layout('layouts.admin', ['title' => 'Issues & Returns Desk']);
    }
}
