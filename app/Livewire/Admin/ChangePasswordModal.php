<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Self-service password change modal for any authenticated staff member.
 * Accessible from the topbar profile menu in the admin operations portal.
 */
class ChangePasswordModal extends Component
{
    public bool $isOpen = false;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    #[On('open-change-password-modal')]
    public function open(): void
    {
        $this->resetValidation();
        $this->current_password = '';
        $this->new_password = '';
        $this->new_password_confirmation = '';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'new_password.different' => 'Your new password cannot be the same as your current password.',
            'new_password.confirmed' => 'The password confirmation does not match.',
            'new_password.min' => 'Your new password must be at least 6 characters.',
        ]);

        $user = auth()->user();

        if (! $user || ! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The current password you entered is incorrect.');

            return;
        }

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->isOpen = false;
        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

        session()->flash('success', 'Your password was changed successfully.');
    }

    public function render()
    {
        return view('livewire.admin.change-password-modal');
    }
}
