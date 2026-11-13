<?php

namespace App\Livewire\Store\Account;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::store')]
#[Title('Saved meals')]
class SavedMeals extends Component
{
    public function remove(int $recipeId): void
    {
        Auth::user()->savedRecipes()->detach($recipeId);
    }

    public function render()
    {
        return view('livewire.store.account.saved-meals', [
            'recipes' => Auth::user()->savedRecipes()->approved()->with('country')->get(),
        ]);
    }
}
