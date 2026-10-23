<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Staff & Access Management Portal.
 * Designed for store owners and general managers to self-serve staff onboarding,
 * reset employee passwords, assign manager vs associate roles, and revoke access.
 */
class StaffManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    // Create Modal State
    public bool $showCreateModal = false;

    public string $create_name = '';

    public string $create_email = '';

    public string $create_phone = '';

    public string $create_role = 'associate';

    public string $create_password = '';

    // Edit Modal State
    public bool $showEditModal = false;

    public ?int $editingUserId = null;

    public string $edit_name = '';

    public string $edit_email = '';

    public string $edit_phone = '';

    public string $edit_role = 'associate';

    public string $edit_password = '';

    // Delete Confirmation State
    public bool $showDeleteModal = false;

    public ?int $deletingUserId = null;

    public string $deletingUserName = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->create_name = '';
        $this->create_email = '';
        $this->create_phone = '';
        $this->create_role = 'associate';
        $this->create_password = 'temp'.rand(1000, 9999).'!#';
        $this->showCreateModal = true;
    }

    public function createStaff(): void
    {
        $this->validate([
            'create_name' => 'required|string|max:255',
            'create_email' => 'required|email|max:255|unique:users,email',
            'create_phone' => 'nullable|string|max:25',
            'create_role' => 'required|in:manager,associate',
            'create_password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => trim($this->create_name),
            'email' => strtolower(trim($this->create_email)),
            'phone' => trim($this->create_phone) ?: null,
            'role' => $this->create_role,
            'password' => Hash::make($this->create_password),
            'is_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->showCreateModal = false;
        session()->flash('success', "Staff account for \"{$user->name}\" created successfully with temporary password.");
    }

    public function openEditModal(int $userId): void
    {
        $this->resetValidation();
        $user = User::findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->edit_name = $user->name;
        $this->edit_email = $user->email;
        $this->edit_phone = $user->phone ?? '';
        $this->edit_role = in_array($user->role, ['manager', 'owner'], true) ? 'manager' : 'associate';
        $this->edit_password = '';

        $this->showEditModal = true;
    }

    public function updateStaff(): void
    {
        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_email' => 'required|email|max:255|unique:users,email,'.$this->editingUserId,
            'edit_phone' => 'nullable|string|max:25',
            'edit_role' => 'required|in:manager,associate',
            'edit_password' => 'nullable|string|min:6',
        ]);

        $user = User::findOrFail($this->editingUserId);

        $data = [
            'name' => trim($this->edit_name),
            'email' => strtolower(trim($this->edit_email)),
            'phone' => trim($this->edit_phone) ?: null,
            'role' => $this->edit_role,
        ];

        if (! empty($this->edit_password)) {
            $data['password'] = Hash::make($this->edit_password);
        }

        $user->update($data);

        $this->showEditModal = false;
        $msg = ! empty($this->edit_password) 
            ? "Staff member \"{$user->name}\" updated with new password."
            : "Staff member \"{$user->name}\" updated successfully.";

        session()->flash('success', $msg);
    }

    public function toggleStatus(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'Security guard: You cannot deactivate your own active session account.');

            return;
        }

        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);

        $state = $user->is_active ? 'activated and can sign in' : 'deactivated and blocked from signing in';
        session()->flash('success', "Account for \"{$user->name}\" is now {$state}.");
    }

    public function openDeleteModal(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'Security guard: You cannot delete your own account.');

            return;
        }

        $user = User::findOrFail($userId);
        $this->deletingUserId = $user->id;
        $this->deletingUserName = $user->name;
        $this->showDeleteModal = true;
    }

    public function confirmDelete(): void
    {
        if (! $this->deletingUserId || $this->deletingUserId === auth()->id()) {
            return;
        }

        $user = User::findOrFail($this->deletingUserId);
        $name = $user->name;

        // If the staff member has processed orders or transactions, revoke admin role rather than hard delete to preserve foreign keys
        $hasOrders = $user->orders()->exists();
        if ($hasOrders) {
            $user->update(['is_admin' => false, 'is_active' => false]);
            $msg = "Staff access revoked for \"{$name}\" (retained as inactive customer for audit history).";
        } else {
            $user->delete();
            $msg = "Staff member \"{$name}\" has been permanently removed.";
        }

        $this->showDeleteModal = false;
        $this->deletingUserId = null;
        session()->flash('success', $msg);
    }

    public function render()
    {
        $query = User::where('is_admin', true)
            ->when($this->search, function ($q) {
                $term = trim($this->search);
                $q->where(fn ($sub) => $sub->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->when($this->roleFilter, function ($q) {
                if ($this->roleFilter === 'manager') {
                    $q->whereIn('role', ['manager', 'owner']);
                } else {
                    $q->where('role', $this->roleFilter);
                }
            })
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'));

        $counts = [
            'total' => User::where('is_admin', true)->count(),
            'managers' => User::where('is_admin', true)->whereIn('role', ['manager', 'owner'])->count(),
            'associates' => User::where('is_admin', true)->where('role', 'associate')->count(),
            'active' => User::where('is_admin', true)->where('is_active', true)->count(),
        ];

        return view('livewire.admin.staff-manager', [
            'staffMembers' => $query->orderByDesc('created_at')->paginate(10),
            'counts' => $counts,
        ])->layout('layouts.admin', ['title' => 'Staff & Team Management', 'header' => 'Staff & Access Management']);
    }
}
