<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class CreateSystemAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vw:create-sysadmin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a system administrator with system-admin role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('Create System Administrator');
        $this->info('========================================');
        $this->newLine();

        // Check if system-admin role exists
        $systemAdminRole = Role::where('name', 'system-admin')->first();
        if (!$systemAdminRole) {
            $this->error('system-admin role not found! Please run migrations and seeders first.');
            return 1;
        }

        // Get email
        $email = $this->ask('Enter email address');
        
        // Validate email
        if (!$this->validateEmail($email)) {
            $this->error('Invalid email format. Please try again.');
            return 1;
        }
        
        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $this->warn('A user with this email already exists!');
            
            if ($this->confirm('Do you want to assign system-admin role to this user?', false)) {
                $existingUser->assignRole($systemAdminRole);
                $this->updateAdminFlags($existingUser);
                $this->info('✓ System-admin role assigned to existing user!');
                return 0;
            }
            
            return 1;
        }
        
        // Get password
        $password = $this->secret('Enter password');
        $passwordConfirmation = $this->secret('Confirm password');
        
        // Validate password
        if ($password !== $passwordConfirmation) {
            $this->error('Passwords do not match!');
            return 1;
        }
        
        if (strlen($password) < 8) {
            $this->error('Password must be at least 8 characters!');
            return 1;
        }
        
        // Get name (optional)
        $name = $this->ask('Enter full name (optional)', 'System Administrator');
        
        // Create the user
        $this->info('Creating system administrator...');
        
        try {
            // Adjust this based on your User model's fillable fields
            $userData = [
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ];
            
            // Add Vexim-specific fields if they exist in your users table
            if (Schema::hasColumn('users', 'username')) {
                $userData['username'] = $email;
            }
            
            if (Schema::hasColumn('users', 'localpart')) {
                $userData['localpart'] = explode('@', $email)[0];
            }
            
            if (Schema::hasColumn('users', 'domain_id')) {
                $userData['domain_id'] = 1; // Assuming domain_id 1 is the system/admin domain
            }
            
            if (Schema::hasColumn('users', 'type')) {
                $userData['type'] = 'admin';
            }
            
            if (Schema::hasColumn('users', 'admin')) {
                $userData['admin'] = 1;
            }
            
            if (Schema::hasColumn('users', 'enabled')) {
                $userData['enabled'] = 1;
            }
            
            $user = User::create($userData);
            
            // Assign system-admin role
            $user->assignRole($systemAdminRole);
            $this->updateAdminFlags($user);
            
            $this->newLine();
            $this->info('========================================');
            $this->info('✓ System Administrator created successfully!');
            $this->info("Email: {$email}");
            $this->info("Name: {$name}");
            $this->info("Role: system-admin");
            $this->info('========================================');
            $this->newLine();
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to create user: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Validate email format
     */
    private function validateEmail($email)
    {
        $validator = Validator::make(['email' => $email], [
            'email' => 'required|email'
        ]);
        
        return !$validator->fails();
    }
    
    /**
     * Update admin flags on the user record
     */
    private function updateAdminFlags($user)
    {
        // Update any additional admin flags in your users table
        if (Schema::hasColumn('users', 'admin')) {
            $user->admin = 1;
        }
        
        if (Schema::hasColumn('users', 'type')) {
            $user->type = 'admin';
        }
        
        if (Schema::hasColumn('users', 'enabled')) {
            $user->enabled = 1;
        }
        
        $user->save();
        
        $this->info('✓ Admin flags updated');
    }
}
