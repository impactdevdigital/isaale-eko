<?php

namespace App\Livewire\Store;

use App\Actions\Issues\OpenOrderIssue;
use App\Models\Order;
use App\Models\OrderIssue;
use App\Models\OrderIssueItem;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Help & Contact: "Report a problem". Every submission opens a case on the
 * Issues Desk. With a matching order number and email, the customer can
 * tick the affected items, so staff can refund or replace from the case.
 */
#[Layout('layouts::store')]
#[Title('Help & Contact')]
class HelpCentre extends Component
{
    /** What the customer can choose, and the Issues Desk case kind it becomes. */
    public const TOPICS = [
        'problem' => 'A problem with my order (missing, damaged, wrong item)',
        'safety' => 'Food safety or allergen concern',
        'return' => 'Return a non-food item',
        'enquiry' => 'A question or something else',
    ];

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $orderNumber = '';

    public string $topic = 'problem';

    public string $description = '';

    /** Honeypot: people leave it empty. */
    public string $website = '';

    #[Locked]
    public ?int $orderId = null;

    /** @var array<int, array{selected: bool, quantity: int, issue_type: string}> */
    public array $items = [];

    #[Locked]
    public ?string $reference = null;

    public function mount(): void
    {
        if ($user = Auth::user()) {
            $this->name = (string) $user->name;
            $this->email = (string) $user->email;
            $this->phone = (string) $user->phone;
        }

        $this->orderNumber = (string) request('order', '');
    }

    /**
     * Find the order so the customer can pick the affected items. Only an
     * order placed with this email (or by the signed-in customer) is shown.
     */
    public function findOrder(): void
    {
        $this->resetErrorBag('orderNumber');
        $this->orderId = null;
        $this->items = [];

        $this->validate(
            ['orderNumber' => ['required', 'string', 'max:40'], 'email' => ['required', 'email:rfc', 'max:255']],
            ['orderNumber.required' => 'Enter your order number.', 'email.required' => 'Enter the email you ordered with.', 'email.email' => 'Enter a valid email address.'],
        );

        if (! $order = $this->matchingOrder()) {
            $this->addError('orderNumber', 'We couldn’t find that order for this email. Check both, or send your message without it.');

            return;
        }

        $this->orderId = $order->id;

        foreach ($order->items as $item) {
            $this->items[$item->id] = ['selected' => false, 'quantity' => $item->quantity, 'issue_type' => OrderIssueItem::TYPE_DAMAGED];
        }
    }

    public function submit(OpenOrderIssue $opener): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'orderNumber' => ['nullable', 'string', 'max:40'],
            'topic' => ['required', 'in:'.implode(',', array_keys(self::TOPICS))],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'name.required' => 'Enter your name.',
            'email.required' => 'Enter your email address.',
            'email.email' => 'Enter a valid email address.',
            'description.required' => 'Tell us what happened.',
            'description.min' => 'Tell us a little more so we can help.',
        ]);

        $key = 'help-form:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('description', 'You’ve sent a few messages already. Try again in a few minutes, or call us.');

            return;
        }

        RateLimiter::hit($key, 600);

        if ($this->website !== '') {
            $this->reference = 'IS-'.now()->format('ymd').'-'.Str::upper(Str::random(4)); // bots get a thank-you, nothing is saved

            return;
        }

        $order = $this->orderId ? Order::with('items')->find($this->orderId) : (trim($this->orderNumber) !== '' ? $this->matchingOrder() : null);
        $lines = $order ? $this->selectedLines($order) : [];
        $contact = [
            'contact_name' => trim($this->name),
            'contact_email' => strtolower(trim($this->email)),
            'contact_phone' => trim($this->phone) ?: null,
        ];

        if ($order && $lines) {
            $issue = $opener->handle($order, $lines, trim($this->description), [], OrderIssue::CHANNEL_WEBSITE, null, null, $this->topic === 'enquiry' ? 'problem' : $this->topic);
            $issue->update($contact);
        } else {
            $issue = OrderIssue::create([
                'reference' => 'IS-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
                'order_id' => $order?->id,
                'user_id' => $order?->user_id ?? Auth::id(),
                ...$contact,
                'kind' => $this->topic,
                'channel' => OrderIssue::CHANNEL_WEBSITE,
                'status' => OrderIssue::STATUS_NEW,
                'description' => trim($this->description),
                'reported_at' => now(),
                'due_at' => now()->addHours((int) Setting::get('issue_response_hours', 24)),
                'item_instruction' => $this->topic === 'safety' ? 'keep' : 'none',
            ]);

            $issue->events()->create(['type' => 'opened', 'actor' => 'customer', 'note' => 'Reported via the website Help form']);
        }

        $this->reference = $issue->reference;
    }

    private function matchingOrder(): ?Order
    {
        $email = strtolower(trim($this->email));
        $number = strtoupper(ltrim(trim($this->orderNumber), '#'));

        return Order::with(['items', 'user'])
            ->where('number', $number)
            ->get()
            ->first(fn (Order $order) => ($email !== '' && (strtolower((string) $order->contact_email) === $email || strtolower((string) $order->user?->email) === $email))
                || (Auth::id() && $order->user_id === Auth::id()));
    }

    /**
     * @return array<int, array{order_item_id: int, quantity: int, issue_type: string}>
     */
    private function selectedLines(Order $order): array
    {
        $types = array_keys(OrderIssueItem::typeLabels());

        return $order->items
            ->filter(fn ($item) => ! empty($this->items[$item->id]['selected']))
            ->map(fn ($item) => [
                'order_item_id' => $item->id,
                'quantity' => max(1, min($item->quantity, (int) ($this->items[$item->id]['quantity'] ?? 1))),
                'issue_type' => in_array($this->items[$item->id]['issue_type'] ?? '', $types, true) ? $this->items[$item->id]['issue_type'] : OrderIssueItem::TYPE_DAMAGED,
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.store.help-centre', [
            'order' => $this->orderId ? Order::with('items')->find($this->orderId) : null,
            'issueTypes' => OrderIssueItem::typeLabels(),
            'supportEmail' => Setting::get('support_email', 'support@isaleeeko.com'),
            'supportPhone' => Setting::get('support_phone'),
        ]);
    }
}
