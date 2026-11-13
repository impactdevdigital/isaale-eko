<?php

namespace App\Livewire\Admin;

use App\Models\PaymentMethod;
use App\Models\SettingChange;
use App\Support\Delivery;
use Livewire\Component;

class PaymentMethods extends Component
{
    public function toggle(int $id): void
    {
        $method = PaymentMethod::findOrFail($id);

        // Never allow switching off the last method that is on
        if ($method->is_enabled && PaymentMethod::where('is_enabled', true)->count() <= 1) {
            $this->addError('toggle', 'At least one payment method must stay enabled for customers to checkout.');

            return;
        }

        $newState = ! $method->is_enabled;
        $method->update(['is_enabled' => $newState]);

        SettingChange::create([
            'key' => "payment_method:{$method->code}",
            'old_value' => ! $newState,
            'new_value' => $newState,
            'user_id' => auth()->id(),
        ]);

        session()->flash('saved', "Updated payment method '{$method->label()}'.");
    }

    public function toggleDelivery(): void
    {
        $enabled = ! Delivery::enabled();
        Delivery::setEnabled($enabled, auth()->id());

        session()->flash('saved', $enabled
            ? 'Delivery is on. Customers can choose Delivery at checkout.'
            : 'Delivery is off. Checkout now offers Pickup only.');
    }

    public function move(int $id, int $direction): void
    {
        $current = PaymentMethod::findOrFail($id);
        $targetOrder = $current->sort_order + $direction;

        if ($targetOrder < 0) {
            return;
        }

        $swapWith = PaymentMethod::where('sort_order', $targetOrder)->first();

        if ($swapWith) {
            $swapWith->update(['sort_order' => $current->sort_order]);
        }

        $current->update(['sort_order' => $targetOrder]);
    }

    public function render()
    {
        return view('livewire.admin.payment-methods', [
            'methods' => PaymentMethod::orderBy('sort_order')->get(),
            'deliveryEnabled' => Delivery::enabled(),
        ])->layout('layouts.admin', ['header' => 'Payment Methods']);
    }
}
