<?php

namespace App\Filament\Resources\EximUsers\Pages;

use App\Filament\Resources\EximUsers\EximUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEximUser extends CreateRecord
{
    protected static string $resource = EximUserResource::class;
}
