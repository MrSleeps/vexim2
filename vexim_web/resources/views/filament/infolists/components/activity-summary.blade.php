@php
    // Get raw data directly from database
    $rawData = DB::table('activity_log')->where('id', $record->id)->first();
    
    $event = $rawData->event;
    $subjectType = class_basename($rawData->subject_type ?? '');
    $causer = $record->causer;
    
    // Get the user who performed the action
    $actor = 'System';
    if ($causer) {
        $actorName = $causer->name ?? 'Unknown User';
        $actorEmail = $causer->email ?? null;
        
        if ($actorEmail) {
            $actor = "{$actorName} ({$actorEmail})";
        } else {
            $actor = $actorName;
        }
    }
    
    // Parse the attribute_changes JSON
    $changes = [];
    $old = [];
    
    if ($rawData && isset($rawData->attribute_changes) && $rawData->attribute_changes) {
        $attributeChanges = json_decode($rawData->attribute_changes, true);
        if (is_array($attributeChanges)) {
            // For created and updated, attributes are in the 'attributes' key
            $changes = $attributeChanges['attributes'] ?? [];
            $old = $attributeChanges['old'] ?? [];
        }
    }
    
    // Load the actual model to get missing data (like type)
    $modelData = null;
    if ($rawData->subject_id && $rawData->subject_type === 'App\Models\EximUser') {
        try {
            $modelData = \App\Models\EximUser::where('user_id', $rawData->subject_id)->first();
        } catch (\Exception $e) {
            // Model might be deleted
        }
    }
    
    // Get type: first from changes, then from old, then from the actual model
    $type = $changes['type'] ?? $old['type'] ?? ($modelData ? $modelData->type : 'unknown');
    
    // Get username: first from changes, then from old, then from the actual model
    $username = $changes['username'] ?? $old['username'] ?? ($modelData ? $modelData->username : 'Unknown');
    
    // For the account type description, also check if it's an alias by looking at smtp/smtp changes
    if ($type === 'unknown' && (isset($changes['smtp']) || isset($old['smtp']))) {
        // If it has smtp forwarding, it's likely an alias
        $type = 'alias';
    }
    
    // Helper function to get account type description
    function getAccountTypeDescription($type) {
        return match($type) {
            'local' => 'mailbox',
            'alias' => 'forwarding alias',
            'fail' => 'rejecting account',
            'catch' => 'catch-all account',
            default => 'account'
        };
    }
    
    $summary = '';
    
    if ($subjectType === 'EximUser') {
        if ($event === 'created') {
            $typeDesc = getAccountTypeDescription($type);
            $emailInfo = $username;
            
            $summary = "📧 <strong>{$actor}</strong> created {$typeDesc}: <strong>{$emailInfo}</strong>";
            
            // Show creation details
            $creationDetails = [];
            
            if ($type === 'alias' && isset($changes['smtp'])) {
                $creationDetails[] = "Forwards to: {$changes['smtp']}";
            } elseif ($type === 'catch' && isset($changes['smtp'])) {
                $creationDetails[] = "Catch-all to: {$changes['smtp']}";
            } elseif ($type === 'fail') {
                $creationDetails[] = "Rejects all email";
            }
            
            // Add forward status if enabled
            if (isset($changes['on_forward']) && $changes['on_forward']) {
                $creationDetails[] = "Forwarding: Enabled";
            }
            
            // Show the creation details
            if (!empty($creationDetails)) {
                foreach ($creationDetails as $detail) {
                    $summary .= "<br>↳ {$detail}";
                }
            }
        } 
        elseif ($event === 'deleted') {
            $typeDesc = getAccountTypeDescription($type);
            $emailInfo = $username;
            if ($type === 'alias' && isset($old['smtp'])) {
                $emailInfo .= " → was forwarding to: {$old['smtp']}";
            } elseif ($type === 'catch' && isset($old['smtp'])) {
                $emailInfo .= " → was catch-all to: {$old['smtp']}";
            } elseif ($type === 'fail') {
                $emailInfo .= " → rejected all email";
            }
            $summary = "🗑️ <strong>{$actor}</strong> deleted {$typeDesc}: <strong>{$emailInfo}</strong>";
        } 
        elseif ($event === 'updated') {
            $typeDesc = getAccountTypeDescription($type);
            $summary = "✏️ <strong>{$actor}</strong> updated {$typeDesc}: <strong>{$username}</strong>";
            
            // Show all changes
            if (!empty($changes)) {
                $hasChanges = false;
                
                foreach ($changes as $field => $newValue) {
                    if ($field === 'updated_at') continue;
                    
                    $hasChanges = true;
                    $oldValue = $old[$field] ?? '(not set)';
                    
                    switch ($field) {
                        case 'smtp':
                            if ($type === 'alias') {
                                $summary .= "<br>↳ Changed forwarding address from <span class='line-through text-red-600'>{$oldValue}</span> → <span class='font-semibold text-green-600'>{$newValue}</span>";
                            } elseif ($type === 'catch') {
                                $summary .= "<br>↳ Changed catch-all destination from <span class='line-through text-red-600'>{$oldValue}</span> → <span class='font-semibold text-green-600'>{$newValue}</span>";
                            } else {
                                $summary .= "<br>↳ Changed SMTP/forwarding address from <span class='line-through text-red-600'>{$oldValue}</span> → <span class='font-semibold text-green-600'>{$newValue}</span>";
                            }
                            break;
                            
                        case 'type':
                            $oldTypeDesc = getAccountTypeDescription($oldValue);
                            $newTypeDesc = getAccountTypeDescription($newValue);
                            $summary .= "<br>↳ Changed account type from <span class='line-through text-red-600'>{$oldTypeDesc}</span> → <span class='font-semibold text-green-600'>{$newTypeDesc}</span>";
                            break;
                            
                        case 'enabled':
                            $oldStatus = $oldValue ? 'Enabled' : 'Disabled';
                            $newStatus = $newValue ? 'Enabled' : 'Disabled';
                            $summary .= "<br>↳ Changed status from <span class='line-through text-red-600'>{$oldStatus}</span> → <span class='font-semibold text-green-600'>{$newStatus}</span>";
                            break;
                            
                        case 'localpart':
                            $oldLocalpart = $oldValue;
                            $newLocalpart = $newValue;
                            $summary .= "<br>↳ Changed local part from <span class='line-through text-red-600'>{$oldLocalpart}</span> → <span class='font-semibold text-green-600'>{$newLocalpart}</span>";
                            break;
                            
                        default:
                            $fieldName = ucfirst(str_replace('_', ' ', $field));
                            $summary .= "<br>↳ {$fieldName}: <span class='line-through text-red-600'>{$oldValue}</span> → <span class='font-semibold text-green-600'>{$newValue}</span>";
                            break;
                    }
                }
                
                if (!$hasChanges) {
                    $summary .= "<br>↳ No fields were changed (only timestamps updated)";
                }
            } else {
                $summary .= "<br>↳ No changes recorded";
            }
        }
    }
@endphp

<div class="prose dark:prose-invert max-w-none">
    <div class="text-sm bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        {!! $summary !!}
    </div>
</div>