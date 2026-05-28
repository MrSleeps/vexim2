<?php

namespace App\Filament\Resources\DomainUsers\Pages;

use App\Filament\Resources\DomainUsers\DomainUserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDomainUser extends EditRecord
{
    protected static string $resource = DomainUserResource::class;
    
    public function getRecord(): Model
    {
        // Always return the logged-in user
        $user = auth()->user();
        
        if (!$user instanceof \App\Models\EximUser) {
            abort(403, 'Unauthorized access.');
        }
        
        return $user;
    }
    
    protected function getRedirectUrl(): string
    {
        // Stay on the same page after save
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
    
    protected function getSavedNotification(): ?\Filament\Notifications\Notification
    {
        return \Filament\Notifications\Notification::make()
            ->success()
            ->title('Account updated')
            ->body('Your account has been updated successfully.');
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Don't show password fields
        unset($data['password']);
        unset($data['password_confirmation']);
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Handle password update separately
        if (!empty($data['password'])) {
            $record->setPasswordAttribute($data['password']);
            unset($data['password']);
        }
        
        unset($data['password_confirmation']);
        
        $record->update($data);
        
        return $record;
    }
    
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
        ];
    }
}