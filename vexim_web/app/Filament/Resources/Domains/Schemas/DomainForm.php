<?php

namespace App\Filament\Resources\Domains\Schemas;

use App\Models\User;
use App\Models\Setting;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Get;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Domain Information')
                    ->schema([
                       TextInput::make('domain')
                        ->required()
                        ->maxLength(255)
                        ->helperText('e.g., example.com')
                        ->live(debounce: 500)  // Updates after 500ms of no typing
                        ->afterStateUpdated(function ($state, callable $set) {
                            $mailRoot = Setting::get('mail_root', '/var/mail/');
                            if ($state) {
                                $set('maildir', rtrim($mailRoot, '/') . '/' . $state);
                            }
                        }),
                        TextInput::make('maildir')
                            ->required()
                            ->maxLength(4096)
                            ->helperText('Path to mail storage directory (auto-generated from domain name)')
                            ->disabled()  // Make it read-only so users can't edit
                            ->dehydrated(true),  // Still save to database
                        Toggle::make('enabled')
                            ->required()
                            ->default(true),
                    ])->columns(2),
                    
                Section::make('Limits & Quotas')
                    ->schema([
                        TextInput::make('max_accounts')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Max Email Accounts')
                            ->helperText('0 = unlimited'),
                        TextInput::make('quotas')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Domain Quota (MB)')
                            ->helperText('0 = unlimited'),
                        TextInput::make('maxmsgsize')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->label('Max Message Size (KB)')
                            ->helperText('0 = unlimited'),
                        TextInput::make('uid')
                            ->required()
                            ->numeric()
                            ->default(65534)
                            ->helperText('System UID for mail storage')
                            ->disabled(function () {
                                // Check if domain-admin is allowed to set UID/GID
                                $allowDomainAdminUidGid = Setting::get('allow_domainadmin_uid_gid', 0);
                                $user = auth()->user();
                                
                                // Disable if setting is 0 AND user is domain-admin
                                return ($allowDomainAdminUidGid == 0 && $user && $user->isDomainAdmin());
                            })
                            ->dehydrated(function () {
                                // Still save the value even if disabled
                                return true;
                            }),
                        TextInput::make('gid')
                            ->required()
                            ->numeric()
                            ->default(65534)
                            ->helperText('System GID for mail storage')
                            ->disabled(function () {
                                // Check if domain-admin is allowed to set UID/GID
                                $allowDomainAdminUidGid = Setting::get('allow_domainadmin_uid_gid', 0);
                                $user = auth()->user();
                                
                                // Disable if setting is 0 AND user is domain-admin
                                return ($allowDomainAdminUidGid == 0 && $user && $user->isDomainAdmin());
                            })
                            ->dehydrated(function () {
                                // Still save the value even if disabled
                                return true;
                            }),
                    ])->columns(2),
                    
                Section::make('Security Features')
                    ->schema([
                        Toggle::make('avscan')
                            ->label('Antivirus Scanning')
                            ->default(false),
                        Toggle::make('spamassassin')
                            ->label('SpamAssassin Filtering')
                            ->default(false),
                        Toggle::make('blocklists')
                            ->label('Blocklists Enabled')
                            ->default(false),
                        Toggle::make('pipe')
                            ->label('Pipe Support')
                            ->default(false),
                        TextInput::make('sa_tag')
                            ->numeric()
                            ->default(0)
                            ->label('Spam Tag Score'),
                        TextInput::make('sa_refuse')
                            ->numeric()
                            ->default(0)
                            ->label('Spam Refuse Score'),
                    ])->columns(2),
                    
                Section::make('Domain Administrators')
                    ->schema([
                        Select::make('administrators')
                            ->label('Domain Administrators')
                            ->options(function () {
                                // Get all users who are domain-admins or system-admins
                                return \App\Models\User::role(['domain-admin', 'system-admin'])
                                    ->get()
                                    ->mapWithKeys(function ($user) {
                                        $roleName = $user->roles->first()?->name;
                                        $roleBadge = $roleName === 'system-admin' ? 'System Admin' : 'Domain Admin';
                                        return [$user->id => "{$user->name} ({$user->email}) {$roleBadge}"];
                                    });
                            })
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Select which web users can administer this domain')
                            ->loadStateFromRelationshipsUsing(function ($component, $record) {
                                // Load existing administrators when editing
                                if ($record) {
                                    $component->state($record->administrators->pluck('id')->toArray());
                                }
                            })
                            ->saveRelationshipsUsing(function ($record, $state) {
                                // Save the selected administrators
                                if ($record) {
                                    $record->administrators()->sync($state ?? []);
                                }
                            }),
                    ]),
                    
                Section::make('Additional Settings')
                    ->schema([
                        TextInput::make('type')
                            ->hidden()
                            ->default('local')
                            ->maxLength(5),
                        Toggle::make('mailinglists')
                            ->label('Mailing Lists Enabled')
                            ->default(false),
                    ])->columns(2),
            ]);
    }
}