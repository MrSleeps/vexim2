<?php

namespace App\Filament\Resources\EximFails\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EximFailForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('domain_id')
                    ->label('Domain')
                    ->relationship('domain', 'domain')
                    ->required()
                    ->searchable()
                    ->preload(),
                    
                TextInput::make('username')
                    ->label('Failed Email Address')
                    ->required()
                    ->maxLength(255)
                    ->helperText('The email address that will fail (e.g., user@example.com)')
                    ->placeholder('user@example.com'),
                    
                Toggle::make('enabled')
                    ->label('Enabled')
                    ->default(true),
                    
                // Hidden fields that get auto-populated
                Hidden::make('type')
                    ->default('fail'),
                    
                Hidden::make('smtp')
                    ->default(':fail:'),
                    
                Hidden::make('pop')
                    ->default(':fail:'),
                    
                Hidden::make('on_forward')
                    ->default(true),
                    
                // Optional: Add these if your schema requires them
                Hidden::make('uid')
                    ->default(5000),
                    
                Hidden::make('gid')
                    ->default(5000),
            ]);
    }
}