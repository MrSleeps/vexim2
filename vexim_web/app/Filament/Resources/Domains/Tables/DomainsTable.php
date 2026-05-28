<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Models\Domain;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('max_accounts')
                    ->label('Max Accounts')
                    ->sortable(),
                TextColumn::make('quotas')
                    ->label('Quota (MB)')
                    ->sortable(),
                IconColumn::make('enabled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('administrators.name')
                    ->label('Administrators')
                    ->badge()
                    ->separator(',')
                    ->limitList(3)
                    ->tooltip(function ($record) {
                        $admins = $record->administrators->pluck('name')->implode(', ');
                        return $admins ?: 'No administrators assigned';
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('enabled')
                    ->options([
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ]),
                SelectFilter::make('spamassassin')
                    ->label('Spam Filtering')
                    ->options([
                        '1' => 'Enabled',
                        '0' => 'Disabled',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}