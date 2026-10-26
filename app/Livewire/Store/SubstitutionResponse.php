<?php

namespace App\Livewire\Store;

use App\Actions\Orders\RespondToSubstitution;
use App\Models\Order;
use App\Models\SubstitutionRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Item Substitution Decision – Ìsàlèè Èkó')]
class SubstitutionResponse extends Component
{
    public string $orderNumber;
    public int $requestId;

    public ?Order $order = null;
    public ?SubstitutionRequest $request = null;
    public bool $isSubmitted = false;
    public string $submissionResult = '';

    public function mount(string $number, int $id): void
    {
        $this->orderNumber = $number;
        $this->requestId = $id;

        $this->order = Order::where('number', $number)->firstOrFail();
        $this->request = SubstitutionRequest::with(['item', 'substitute.product'])
            ->where('order_id', $this->order->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $this->request->isPending()) {
            $this->isSubmitted = true;
            $this->submissionResult = $this->request->status;
        }
    }

    public function approve(RespondToSubstitution $respond): void
    {
        if (! $this->request->isPending()) {
            return;
        }

        $respond->handle($this->request, 'approve', 'customer_link');

        $this->request->refresh();
        $this->isSubmitted = true;
        $this->submissionResult = 'approved';
        session()->flash('success', 'Thank you! The substitute item has been confirmed for your order.');
    }

    public function decline(RespondToSubstitution $respond): void
    {
        if (! $this->request->isPending()) {
            return;
        }

        $respond->handle($this->request, 'decline', 'customer_link');

        $this->request->refresh();
        $this->isSubmitted = true;
        $this->submissionResult = 'declined';
        session()->flash('info', 'We have removed the unavailable item from your order. A refund has been issued to your original payment method.');
    }

    public function render()
    {
        return view('livewire.store.substitution-response');
    }
}
