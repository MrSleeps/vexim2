<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    // Navigation label (what appears in the menu)
    protected static ?string $navigationLabel = 'Website Accounts';    

    protected static ?string $recordTitleAttribute = 'Website Users';

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();
    
    if (!$user) {
        return $query->whereRaw('1 = 0');
    }
    
    // System admins see all web users
    if ($user->isSystemAdmin()) {
        return $query;
    }
    
    // Domain admins only see:
    // 1. Themselves
    // 2. Other domain-admins within their domains
    if ($user->isDomainAdmin()) {
        // Specify the table for domain_id - use 'domain_user.domain_id'
        $domainIds = $user->domains()->pluck('domains.domain_id'); // Specify domains table
        
        return $query->where(function ($q) use ($user, $domainIds) {
            // The current user
            $q->where('users_web.id', $user->id)  // Specify users_web table
              // Other domain-admins who share the same domains
              ->orWhereHas('domains', function ($dq) use ($domainIds) {
                  $dq->whereIn('domains.domain_id', $domainIds);  // Specify domains table
              })
              // Only show domain-admin role users (not system-admins)
              ->whereHas('roles', function ($rq) {
                  $rq->where('name', 'domain-admin');
              });
        });
    }
    
    return $query->whereRaw('1 = 0');
}
    
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }
    
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        
        if (!$user) return false;
        
        // System admins can edit anyone
        if ($user->isSystemAdmin()) return true;
        
        // Domain admins can only edit domain-admins within their domains
        if ($user->isDomainAdmin()) {
            // Get domains this admin manages
            $adminDomainIds = $user->domains()->pluck('domain_id');
            
            // Check if the target user is a domain-admin and shares at least one domain
            if ($record->hasRole('domain-admin')) {
                $recordDomainIds = $record->domains()->pluck('domain_id');
                return $recordDomainIds->intersect($adminDomainIds)->isNotEmpty();
            }
        }
        
        return false;
    }
    
    public static function canDelete($record): bool
    {
        $user = auth()->user();
        
        // Only system admins can delete users
        return $user && $user->isSystemAdmin();
    }    

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}