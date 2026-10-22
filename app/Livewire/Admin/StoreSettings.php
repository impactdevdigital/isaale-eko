<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Livewire\Component;

class StoreSettings extends Component
{
    public string $group = 'pickup';
    public array $values = [];

    public function mount(): void
    {
        if (auth()->check() && !auth()->user()->can('manage-store-settings')) {
            // Permission check when permissions are seeded
        }

        $this->load();
    }

    public function updatedGroup(): void
    {
        $this->load();
    }

    public function selectGroup(string $groupKey): void
    {
        $this->group = $groupKey;
        $this->load();
    }

    private function load(): void
    {
        $this->values = [];
        $fields = config("shop_settings.{$this->group}.fields", []);

        foreach ($fields as $key => $field) {
            $this->values[$key] = Setting::get($key);
        }
    }

    public function save(): void
    {
        $fields = config("shop_settings.{$this->group}.fields", []);

        $rules = collect($fields)->mapWithKeys(fn ($f, $k) => ["values.{$k}" => $f['rules']])->all();
        $this->validate($rules);

        foreach ($fields as $key => $f) {
            $val = $this->values[$key];
            $val = match ($f['type']) {
                'number' => (int) $val,
                'bool' => (bool) $val,
                default => $val,
            };
            Setting::put($key, $val, auth()->id());
        }

        session()->flash('saved', 'Store settings updated successfully. New checkouts will apply them immediately.');
    }

    public function render()
    {
        return view('livewire.admin.store-settings', [
            'groups' => config('shop_settings', []),
            'currentGroup' => config("shop_settings.{$this->group}", []),
        ])->layout('layouts.admin', ['header' => 'Store Operations & Settings']);
    }
}
