<?php

namespace App\Filament\Resources\Whitelists;

use App\Filament\Resources\Whitelists\Pages\CreateWhitelist;
use App\Filament\Resources\Whitelists\Pages\EditWhitelist;
use App\Filament\Resources\Whitelists\Pages\ListWhitelists;
use App\Filament\Resources\Whitelists\Schemas\WhitelistForm;
use App\Filament\Resources\Whitelists\Tables\WhitelistsTable;
use App\Models\Whitelist;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhitelistResource extends Resource
{
    protected static ?string $model = Whitelist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Lists';
    
    protected static ?string $navigationLabel = 'Whitelist';
    
    protected static ?string $recordTitleAttribute = 'Whitelist';

    public static function form(Schema $schema): Schema
    {
        return WhitelistForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelistsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWhitelists::route('/'),
            'create' => CreateWhitelist::route('/create'),
            'edit' => EditWhitelist::route('/{record}/edit'),
        ];
    }

    /**
     * Apply permissions to the query based on user role
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // System admin can see all whitelist entries
        if ($user->isSystemAdmin()) {
            return $query;
        }

        // Domain admin can see whitelist entries for their domains only
        if ($user->isDomainAdmin()) {
            $domainIds = $user->domains()->pluck('domains.domain_id');
            return $query->whereIn('domain_id', $domainIds);
        }

        // Domain user can see whitelist entries for their own user only
        if ($user->isDomainUser()) {
            // Get the user's exim user record to find their localpart
            $eximUser = \App\Models\EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $query->where('domain_id', $eximUser->domain_id)
                    ->where('localpart', $eximUser->localpart);
            }
            
            return $query->whereRaw('1 = 0');
        }

        // Default: no access
        return $query->whereRaw('1 = 0');
    }

    /**
     * Determine who can create whitelist entries
     * System admins and domain admins can create
     */
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSystemAdmin() || $user->isDomainAdmin());
    }

    /**
     * Determine who can edit whitelist entries
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can edit any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can edit entries for their domains
        if ($user->isDomainAdmin()) {
            return $user->domains()->where('domain_id', $record->domain_id)->exists();
        }

        // Domain user can edit their own entries
        if ($user->isDomainUser()) {
            $eximUser = \App\Models\EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->localpart === $eximUser->localpart;
            }
        }

        return false;
    }

    /**
     * Determine who can delete whitelist entries
     * Only system admins can delete (or adjust as needed)
     */
    public static function canDelete($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // Only system admins can delete
        if ($user->isSystemAdmin()) return true;

        // Optional: Allow domain admins to delete their own entries
        if ($user->isDomainAdmin()) {
             return $user->domains()->where('domain_id', $record->domain_id)->exists();
        }

        // Optional: Allow users to delete their own entries
        if ($user->isDomainUser()) {
             $eximUser = \App\Models\EximUser::where('username', $user->email)->first();
             if ($eximUser) {
                 return $record->domain_id === $eximUser->domain_id && 
                        $record->localpart === $eximUser->localpart;
             }
         }

        return false;
    }

    /**
     * Determine if a record can be viewed
     */
    public static function canView($record): bool
    {
        $user = auth()->user();

        if (!$user) return false;

        // System admin can view any
        if ($user->isSystemAdmin()) return true;

        // Domain admin can view entries for their domains
        if ($user->isDomainAdmin()) {
            return $user->domains()->where('domain_id', $record->domain_id)->exists();
        }

        // Domain user can view their own entries
        if ($user->isDomainUser()) {
            $eximUser = \App\Models\EximUser::where('username', $user->email)
                ->whereIn('type', ['local', 'alias', 'catch'])
                ->first();
            
            if ($eximUser) {
                return $record->domain_id === $eximUser->domain_id && 
                       $record->localpart === $eximUser->localpart;
            }
        }

        return false;
    }

    /**
     * Optional: Disable create for domain users in the UI
     */
    public static function canCreateWithPermission(): bool
    {
        return static::canCreate();
    }
}