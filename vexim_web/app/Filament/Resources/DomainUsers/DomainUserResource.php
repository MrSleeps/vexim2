<?php

namespace App\Filament\Resources\DomainUsers;

use App\Models\EximUser;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section as FormSection;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\DomainUsers\Schemas\DomainUserForm;

class DomainUserResource extends Resource
{
    protected static ?string $model = EximUser::class;
    
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserCircle;
    
    protected static string|\UnitEnum|null $navigationGroup = null;
    
    protected static ?string $navigationLabel = 'My Email Account';
    
    protected static ?string $label = 'My Email Account';
    
    protected static ?string $pluralLabel = 'My Email Account';
    
    protected static ?int $navigationSort = -1;
    
    /**
     * This is key - we need canViewAny to return true for navigation to show
     */
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user instanceof EximUser;
    }
    
    /**
     * Register navigation only for EximUser
     */
    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user instanceof EximUser;
    }
    
    /**
     * Users can only edit their own account
     */
    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return $user instanceof EximUser && $user->getKey() === $record->getKey();
    }
    
    public static function canCreate(): bool
    {
        return false;
    }
    
    public static function canDelete($record): bool
    {
        return false;
    }
    
    public static function canView($record): bool
    {
        // Allow view so the navigation works
        return true;
    }
    
    public static function getRecord(): ?EximUser
    {
        $user = auth()->user();
        return $user instanceof EximUser ? $user : null;
    }
    
    public static function form(Schema $schema): Schema
    {
        return DomainUserForm::configure($schema);
    }
    
    public static function afterSave($record, $data): void
    {
        if (!empty($data['password'])) {
            $record->setPasswordAttribute($data['password']);
            $record->save();
            
            Notification::make()
                ->title('Password updated successfully')
                ->success()
                ->send();
        }
        
        Notification::make()
            ->title('Account updated successfully')
            ->success()
            ->send();
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\DomainUsers\Pages\ListDomainUsers::route('/'),
            'edit' => \App\Filament\Resources\DomainUsers\Pages\EditDomainUser::route('/{record}/edit'),
        ];
    }
    
    public static function getUrl(?string $name = 'edit', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false, ?string $configuration = null): string
    {
        // For navigation, use the index page which will redirect
        if ($name === 'edit' && empty($parameters)) {
            $name = 'index';
        }
        
        // Make sure the record parameter is set for edit
        if ($name === 'edit' && empty($parameters)) {
            $user = auth()->user();
            if ($user instanceof EximUser) {
                $parameters = ['record' => $user->getKey()];
            }
        }
        
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters, $configuration);
    }
    
    /**
     * Override the navigation URL to point to index
     */
    public static function getNavigationUrl(): string
    {
        return static::getUrl('index');
    }
}