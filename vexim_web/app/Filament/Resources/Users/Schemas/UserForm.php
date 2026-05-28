<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\Domain;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        DateTimePicker::make('email_verified_at'),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])->columns(2),
                    
                Section::make('Role Assignment')
                    ->schema([
                        Select::make('roles')
                            ->label('User Level / Role')
                            ->options(function () {
                                $user = auth()->user();
                                
                                // System admins see all roles
                                if ($user && $user->isSystemAdmin()) {
                                    return [
                                        'system-admin' => 'System Admin',
                                        'domain-admin' => 'Domain Admin',
                                        'domain-user' => 'Domain User',
                                    ];
                                }
                                
                                // Domain admins can only assign domain-admin role
                                if ($user && $user->isDomainAdmin()) {
                                    return [
                                        'domain-admin' => 'Domain Admin',
                                    ];
                                }
                                
                                return [];
                            })
                            ->required()
                            ->native(false)
                            ->helperText(function () {
                                $user = auth()->user();
                                if ($user && $user->isSystemAdmin()) {
                                    return 'system-admin: Full access to everything | domain-admin: Access only to their assigned domains | domain-user: Email account owners';
                                }
                                return 'domain-admin: Access only to their assigned domains';
                            })
                            ->loadStateFromRelationshipsUsing(function (Select $component, $record) {
                                if ($record) {
                                    $component->state($record->roles->first()?->name);
                                }
                            })
                            ->saveRelationshipsUsing(function ($record, $state) {
                                if ($record && filled($state)) {
                                    $record->syncRoles([$state]);
                                }
                            }),
                    ]),
                    
                Section::make('Domain Administration')

                    ->schema([
                        Select::make('domains')
                            ->label('Assigned Domains')
                            ->options(function () {
                                $user = auth()->user();
                                
                                // System admins can assign any domain
                                if ($user && $user->isSystemAdmin()) {
                                    return Domain::pluck('domain', 'domain_id');
                                }
                                
                                // Domain admins can only assign domains they manage
                                if ($user && $user->isDomainAdmin()) {
                                    return $user->domains()->pluck('domain', 'domain_id');
                                }
                                
                                return [];
                            })
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText(function () {
                                $user = auth()->user();
                                if ($user && $user->isSystemAdmin()) {
                                    return 'Select which domains this user can administer';
                                }
                                return 'Select which domains this domain-admin can manage (only domains you manage)';
                            })
                            ->loadStateFromRelationshipsUsing(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->domains->pluck('domain_id')->toArray());
                                }
                            })
                            ->saveRelationshipsUsing(function ($record, $state) {
                                if ($record) {
                                    // When saving, attach with the correct pivot role
                                    $record->domains()->syncWithPivotValues(
                                        $state ?? [],
                                        ['role' => 'domain-admin']
                                    );
                                }
                            }),
                    ])
                    ->visible(fn ($get) => in_array($get('roles'), ['system-admin', 'domain-admin'])),
            ]);
    }
}