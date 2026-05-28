<?php

namespace App\Filament\Resources\EximUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EximUserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('domain_id')
                    ->relationship('domain', 'domain')
                    ->required(),
                TextInput::make('localpart')
                    ->required()
                    ->default(''),
                TextInput::make('username')
                    ->required()
                    ->default(''),
                TextInput::make('crypt')
                    ->default(null),
                TextInput::make('uid')
                    ->required()
                    ->numeric()
                    ->default(65534),
                TextInput::make('gid')
                    ->required()
                    ->numeric()
                    ->default(65534),
                TextInput::make('smtp')
                    ->default(null),
                TextInput::make('pop')
                    ->default(null),
                Select::make('type')
                    ->options([
                        'local' => 'Local',
                        'alias' => 'Alias',
                        'catch' => 'Catch',
                        'fail' => 'Fail',
                        'piped' => 'Piped',
                        'admin' => 'Admin',
                        'site' => 'Site',
                    ])
                    ->default('local')
                    ->required(),
                Toggle::make('admin')
                    ->required(),
                Toggle::make('on_avscan')
                    ->required(),
                Toggle::make('on_blocklist')
                    ->required(),

                Toggle::make('on_piped')
                    ->required(),
                Toggle::make('on_spamassassin')
                    ->required(),
                Toggle::make('spam_drop')
                    ->required(),
                Toggle::make('enabled')
                    ->required(),
                TextInput::make('flags')
                    ->default(null),
                Toggle::make('on_forward')
                    ->label('Also forward the message')
                    ->required(),
                TextInput::make('forward')
                    ->label('Forward the message to')
                    ->default(null),
                Toggle::make('unseen')
                    ->required(),
                TextInput::make('maxmsgsize')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('quota')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('realname')
                    ->default(null),
                TextInput::make('sa_tag')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sa_refuse')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tagline')
                    ->default(null),
                Toggle::make('on_vacation')
                    ->label('Vacation Mode')
                    ->required(),
                Textarea::make('vacation')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
