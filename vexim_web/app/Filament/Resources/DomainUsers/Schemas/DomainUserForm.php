<?php

namespace App\Filament\Resources\DomainUsers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class DomainUserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        
        return $schema
            ->components([
                Section::make('Account Information')
                    ->description('Update your email account settings')
                    ->schema([
                        TextInput::make('realname')
                            ->label('Display Name')
                            ->placeholder('Your full name')
                            ->maxLength(255)
                            ->helperText('This name will appear on outgoing emails'),
                        
                        TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->placeholder('Leave blank to keep current password')
                            ->maxLength(255)
                            ->helperText('Password must be at least 8 characters')
                            ->rule('min:8'),
                        
                        TextInput::make('password_confirmation')
                            ->label('Confirm New Password')
                            ->password()
                            ->same('password')
                            ->placeholder('Confirm your new password'),
                    ])->columns(2),
                
                Section::make('Account Details')
                    ->description('Your account information (read-only)')
                    ->schema([
                        TextInput::make('username')
                            ->label('Email Address')
                            ->disabled()
                            ->default($user->username),
                        
                        TextInput::make('quota')
                            ->label('Quota (MB)')
                            ->disabled()
                            ->default($user->quota),
                        
                        TextInput::make('enabled')
                            ->label('Status')
                            ->disabled()
                            ->default($user->enabled ? 'Active' : 'Disabled'),
                    ])->columns(2),
            ]);
    }
}