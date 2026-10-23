<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateStaffUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'staff:create 
                            {email : The email address for the staff member}
                            {--name=Store Staff : The full name of the staff member}
                            {--password= : The initial password (default: password123)}
                            {--role=manager : The access role (manager or associate)}';

    /**
     * The console command description.
     */
    protected $description = 'Create or reset a store operations staff account for client handover and emergency recovery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = trim($this->option('name') ?: 'Store Staff');
        $password = $this->option('password') ?: 'password123';
        $role = in_array(strtolower($this->option('role')), ['manager', 'owner'], true) ? User::ROLE_MANAGER : User::ROLE_ASSOCIATE;

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role' => $role,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->info("Staff account [{$user->name}] successfully configured!");
        $this->table(
            ['Email', 'Role', 'Status', 'Temporary Password', 'Login Portal'],
            [
                [$user->email, ucfirst($user->role), 'Active', $password, url('/admin/login')],
            ]
        );

        return self::SUCCESS;
    }
}
