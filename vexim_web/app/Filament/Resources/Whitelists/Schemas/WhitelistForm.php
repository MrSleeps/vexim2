<?php

namespace App\Filament\Resources\Whitelists\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use App\Models\Domain;
use App\Models\EximUser;

class WhitelistForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // For system admins and domain admins - visible select
                Select::make('domain_id')
                    ->label('Domain')
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->isSystemAdmin()) {
                            return Domain::pluck('domain', 'domain_id');
                        }

                        if ($user->isDomainAdmin()) {
                            return $user->domains()
                                ->pluck('domains.domain', 'domains.domain_id');
                        }

                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50)
                    ->visible(fn () => !auth()->user()->isDomainUser())  // Only show for admins
                    ->live(),

                // For domain users - hidden field with their domain
                Hidden::make('domain_id')
                    ->default(function () {
                        $user = auth()->user();
                        if ($user->isDomainUser()) {
                            $eximUser = EximUser::where('username', $user->email)
                                ->whereIn('type', ['local', 'alias', 'catch'])
                                ->first();
                            return $eximUser?->domain_id;
                        }
                        return null;
                    })
                    ->required()
                    ->visible(fn () => auth()->user()->isDomainUser()),

                Checkbox::make('domain_wide')
                    ->label('Apply to entire domain')
                    ->helperText('Allow this sender for all users in the domain')
                    ->visible(fn () => !auth()->user()->isDomainUser())  // Only show for admins
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('user_id', null);
                        }
                    }),

                // For domain users - they can only create for themselves, so no domain_wide option
                Select::make('user_id')
                    ->label('User')
                    ->options(function ($get) {
                        $user = auth()->user();
                        
                        // For system admins and domain admins
                        if (!$user->isDomainUser()) {
                            $domainId = $get('domain_id');

                            if (empty($domainId)) {
                                return [];
                            }

                            // System-admin
                            if ($user->isSystemAdmin()) {
                                $query = EximUser::query()
                                    ->whereIn('type', ['local', 'alias', 'catch'])
                                    ->where('domain_id', $domainId);

                                return $query->get()
                                    ->mapWithKeys(fn ($eximUser) => [
                                        $eximUser->user_id => 
                                            $eximUser->type === 'catch' 
                                                ? '📦 Catchall Account (' . $eximUser->localpart . ')'
                                                : ($eximUser->type === 'alias'
                                                    ? '🔗 Alias: ' . $eximUser->username
                                                    : '👤 ' . $eximUser->username),
                                    ])
                                    ->toArray();
                            }

                            // Domain-admin
                            if ($user->isDomainAdmin()) {
                                $domainIds = $user->domains()
                                    ->pluck('domains.domain_id')
                                    ->toArray();

                                if (!in_array($domainId, $domainIds)) {
                                    return [];
                                }

                                $query = EximUser::where('domain_id', $domainId)
                                    ->whereIn('type', ['local', 'alias', 'catch']);

                                return $query->get()
                                    ->mapWithKeys(fn ($eximUser) => [
                                        $eximUser->user_id => 
                                            $eximUser->type === 'catch' 
                                                ? '📦 Catchall Account (' . $eximUser->localpart . ')'
                                                : ($eximUser->type === 'alias'
                                                    ? '🔗 Alias: ' . $eximUser->username
                                                    : '👤 ' . $eximUser->username),
                                    ])
                                    ->toArray();
                            }
                        }

                        return [];
                    })
                    ->searchable()
                    ->preload()
                    ->optionsLimit(50)
                    ->hidden(function ($get) {
                        // Hide for domain users or when domain_wide is checked
                        return auth()->user()->isDomainUser() || $get('domain_wide');
                    })
                    ->required(function ($get) {
                        // Required for admins when domain_wide is not checked
                        return !auth()->user()->isDomainUser() && !$get('domain_wide');
                    })
                    ->disabled(function ($get) {
                        // Disabled for admins when no domain selected
                        return !auth()->user()->isDomainUser() && empty($get('domain_id'));
                    })
                    ->live(),

                // For domain users - auto-select their user_id
                Hidden::make('user_id')
                    ->default(function () {
                        $user = auth()->user();
                        if ($user->isDomainUser()) {
                            $eximUser = EximUser::where('username', $user->email)
                                ->whereIn('type', ['local', 'alias', 'catch'])
                                ->first();
                            return $eximUser?->user_id;
                        }
                        return null;
                    })
                    ->required(fn () => auth()->user()->isDomainUser()),

                TextInput::make('localpart')
                    ->hidden()
                    ->dehydrated(true),

                TextInput::make('sender')
                    ->required()
                    ->placeholder('e.g., trusted@example.com or @trusteddomain.com')
                    ->helperText('Can be a full email address or domain (starting with @)'),

                TextInput::make('comment')
                    ->default(null)
                    ->placeholder('Optional comment about this whitelist entry'),
            ]);
    }
}