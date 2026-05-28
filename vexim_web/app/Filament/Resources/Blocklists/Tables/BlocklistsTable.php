<?php

namespace App\Filament\Resources\Blocklists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlocklistsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain.domain_id')
                    ->searchable(),
                TextColumn::make('user.user_id')
                    ->searchable(),
                TextColumn::make('blockhdr')
                    ->searchable(),
                TextColumn::make('blockval')
                    ->searchable(),
                TextColumn::make('color')
                    ->searchable(),
            ])
            ->filters([
                //
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
