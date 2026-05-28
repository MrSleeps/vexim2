<?php

namespace App\Auth;

use App\Models\User;
use App\Models\EximUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class MultiTableUserProvider implements UserProvider
{
    protected $cryptScheme;
    
    public function __construct()
    {
        // Get the crypt scheme from .env, default to sha256
        $this->cryptScheme = env('VEXIM_CRYPT_SCHEME', 'sha256');
    }
    
    public function retrieveById($identifier)
    {
        // First check users_web table
        $user = User::find($identifier);
        if ($user) {
            return $user;
        }
        
        // Then check eximusers table using user_id
        $eximUser = EximUser::find($identifier);
        if ($eximUser) {
            // Assign domain-user role if not already assigned
            if (!$eximUser->hasRole('domain-user')) {
                $eximUser->assignRole('domain-user');
            }
            return $eximUser;
        }
        
        return null;
    }
    
    public function retrieveByToken($identifier, $token)
    {
        $user = User::where('remember_token', $token)->first();
        if ($user) {
            return $user;
        }
        
        $eximUser = EximUser::where('remember_token', $token)->first();
        if ($eximUser) {
            if (!$eximUser->hasRole('domain-user')) {
                $eximUser->assignRole('domain-user');
            }
            return $eximUser;
        }
        
        return null;
    }
    
    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);
        $user->save();
    }
    
    public function retrieveByCredentials(array $credentials)
    {
        $username = $credentials['email'] ?? $credentials['username'] ?? null;
        
        if (!$username) {
            return null;
        }
        
        // Check users_web table first by email
        $user = User::where('email', $username)->first();
        if ($user) {
            return $user;
        }
        
        // Check eximusers table by username
        $eximUser = EximUser::where('username', $username)->first();
        if ($eximUser) {
            // Assign domain-user role if not already assigned
            if (!$eximUser->hasRole('domain-user')) {
                $eximUser->assignRole('domain-user');
            }
            return $eximUser;
        }
        
        return null;
    }
    
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $password = $credentials['password'];
        
        // For EximUser model - use the verifyPassword method
        if ($user instanceof EximUser) {
            return $user->verifyPassword($password);
        }
        
        // For regular User model (users_web table)
        if ($user instanceof User) {
            return Hash::check($password, $user->getAuthPassword());
        }
        
        return false;
    }
    
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        return false;
    }
}