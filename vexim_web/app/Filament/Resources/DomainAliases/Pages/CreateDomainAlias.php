<?php

namespace App\Filament\Resources\DomainAliases\Pages;

use App\Filament\Resources\DomainAliases\DomainAliasResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainAlias extends CreateRecord
{
    protected static string $resource = DomainAliasResource::class;
}
