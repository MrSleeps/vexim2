<?php

namespace App\Filament\Resources\Domains\Pages;

use App\Filament\Resources\Domains\DomainResource;
use App\Models\Setting;
use Filament\Resources\Pages\CreateRecord;

class CreateDomain extends CreateRecord
{
    protected static string $resource = DomainResource::class;
    
    protected function fillForm(): void
    {
        // Get the default values from settings
        $defaults = [
            'type' => 'local',
            'enabled' => true,
            'maildir' => Setting::get('mail_root', ''),
            'uid' => Setting::get('default_uid', ''),
            'gid' => Setting::get('default_gid', ''),
            'max_accounts' => Setting::get('default_max_users', 10),
            'maxmsgsize' => Setting::get('default_max_message_size', 0),
            'quotas' => Setting::get('default_max_storage', 0), 
            'sa_tag' => Setting::get('spam_tag_threshold', 2),
            'sa_refuse' => Setting::get('spam_refuse_threshold', 5),
            'avscan' => Setting::get('default_av_setting', true),
            'spamassassin' => Setting::get('default_spam_setting', false),
            'blocklist' => Setting::get('default_blocklist_setting', false),
            'pipe' => Setting::get('default_pipe_setting', false),
        ];
        
        // Fill only the fields that have values from settings
        $data = [];
        foreach ($defaults as $field => $value) {
            if ($value !== '' && $value !== null) {
                $data[$field] = $value;
            }
        }
        
        // Only fill if there's data
        if (!empty($data)) {
            $this->form->fill($data);
        }
    }
}