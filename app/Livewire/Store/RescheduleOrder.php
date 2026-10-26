<?php

namespace App\Livewire\Store;

use App\Actions\Orders\RescheduleOrder as RescheduleOrderAction;
use App\Models\Order;
use App\Services\Store\StoreHours;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Reschedule Pickup – Ìsàlèè Èkó')]
class RescheduleOrder extends Component
{
    public string $orderNumber;

    public ?Order $order = null;

    public string $selectedDate = '';

    public string $selectedSlot = '';

    public bool $isSubmitted = false;

    public string $errorMessage = '';

    public function mount(string $number, StoreHours $storeHours): void
    {
        $this->orderNumber = $number;
        $this->order = Order::with('items')
            ->where('number', $number)
            ->firstOrFail();

        $days = $storeHours->pickableDays();
        if ($days->isNotEmpty()) {
            $this->selectedDate = $days->first()->toDateString();
            $slots = $storeHours->slotsFor($days->first());
            if ($slots->isNotEmpty()) {
                $this->selectedSlot = $slots->first()->format('H:i');
            }
        }
    }

    public function updatedSelectedDate(StoreHours $storeHours): void
    {
        if ($this->selectedDate) {
            $slots = $storeHours->slotsFor(Carbon::parse($this->selectedDate));
            $this->selectedSlot = $slots->isNotEmpty() ? $slots->first()->format('H:i') : '';
        }
    }

    public function submit(StoreHours $storeHours, RescheduleOrderAction $reschedule): void
    {
        $this->errorMessage = '';

        if (! $this->selectedDate || ! $this->selectedSlot) {
            $this->errorMessage = 'Please choose a collection date and time.';
            return;
        }

        try {
            $dt = Carbon::parse("{$this->selectedDate} {$this->selectedSlot}", config('shop.timezone', 'America/Moncton'));
            $this->order = $reschedule->handle($this->order, $dt, 'customer');
            $this->isSubmitted = true;
            session()->flash('success', 'Your pickup time has been successfully rescheduled!');
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(StoreHours $storeHours)
    {
        $days = $storeHours->pickableDays();
        $availableSlots = $this->selectedDate
            ? $storeHours->slotsFor(Carbon::parse($this->selectedDate))
            : collect();

        return view('livewire.store.reschedule-order', [
            'days' => $days,
            'availableSlots' => $availableSlots,
        ]);
    }
}
