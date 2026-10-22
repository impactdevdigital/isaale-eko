<?php

namespace App\Livewire\Admin;

use App\Models\SettingChange;
use App\Models\StoreClosure;
use App\Models\StoreHour;
use Livewire\Component;

class StoreHoursEditor extends Component
{
    public array $rows = [];
    public string $closureDate = '';
    public string $closureReason = '';

    public function mount(): void
    {
        $this->loadHours();
    }

    private function loadHours(): void
    {
        $this->rows = StoreHour::orderBy('weekday')->get()->map(function ($h) {
            return [
                'id' => $h->id,
                'weekday' => $h->weekday,
                'day_name' => $h->weekdayName(),
                'is_closed' => (bool) $h->is_closed,
                'opens_at' => $h->opens_at ? substr((string) $h->opens_at, 0, 5) : '09:00',
                'closes_at' => $h->closes_at ? substr((string) $h->closes_at, 0, 5) : '19:00',
            ];
        })->all();
    }

    public function save(): void
    {
        $hasError = false;

        foreach ($this->rows as $i => $row) {
            if ($row['is_closed']) {
                continue;
            }

            $valid = preg_match('/^\d{2}:\d{2}$/', $row['opens_at'])
                && preg_match('/^\d{2}:\d{2}$/', $row['closes_at'])
                && $row['closes_at'] > $row['opens_at'];

            if (!$valid) {
                $this->addError("rows.{$i}.closes_at", 'Must use HH:MM format and closing must be after opening.');
                $hasError = true;
            }
        }

        if ($hasError) {
            return;
        }

        $old = StoreHour::orderBy('weekday')->get()->toArray();

        foreach ($this->rows as $row) {
            StoreHour::where('weekday', $row['weekday'])->update([
                'is_closed' => $row['is_closed'],
                'opens_at' => $row['is_closed'] ? null : $row['opens_at'],
                'closes_at' => $row['is_closed'] ? null : $row['closes_at'],
            ]);
        }

        SettingChange::create([
            'key' => 'store_hours',
            'old_value' => $old,
            'new_value' => $this->rows,
            'user_id' => auth()->id(),
        ]);

        session()->flash('saved_hours', 'Weekly opening hours saved. New pickup checkouts apply them immediately.');
        $this->loadHours();
    }

    public function addClosure(): void
    {
        $this->validate([
            'closureDate' => 'required|date|after_or_equal:today',
            'closureReason' => 'nullable|string|max:120',
        ]);

        StoreClosure::updateOrCreate(
            ['date' => $this->closureDate],
            ['reason' => $this->closureReason ?: null]
        );

        $this->reset('closureDate', 'closureReason');
        session()->flash('saved_closure', 'Closure date added.');
    }

    public function removeClosure(int $id): void
    {
        StoreClosure::whereKey($id)->delete();
        session()->flash('saved_closure', 'Closure removed.');
    }

    public function render()
    {
        return view('livewire.admin.store-hours-editor', [
            'closures' => StoreClosure::orderBy('date')->get(),
        ])->layout('layouts.admin', ['header' => 'Store Hours & Closures']);
    }
}
