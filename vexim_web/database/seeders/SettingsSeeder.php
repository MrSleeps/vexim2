<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // IMAP & Quota
            ['key' => 'imap_quota_server', 'value' => '{mail.CHANGE.com:143/imap/notls}', 'type' => 'string', 'description' => 'IMAP server used to check user quotas'],
            ['key' => 'check_quota_via_imap', 'value' => '0', 'type' => 'integer', 'description' => 'Whether to check quota via IMAP'],
            
            // Login & Access
            ['key' => 'allow_user_login', 'value' => '1', 'type' => 'integer', 'description' => 'Allow non-admin users to login (0 = admins only)'],
            
            // Security & Passwords
            ['key' => 'crypt_scheme', 'value' => 'sha512', 'type' => 'string', 'description' => 'Password hash scheme (sha512/bcrypt)'],
            
            // Domain Guessing
            ['key' => 'domain_guess_enabled', 'value' => '0', 'type' => 'integer', 'description' => 'Guess domain from hostname'],
            ['key' => 'domain_guess_left_trim', 'value' => 'mail|vexim', 'type' => 'string', 'description' => 'String to trim left from hostname for domain guess'],
            
            // UID/GID
            ['key' => 'default_uid', 'value' => '90', 'type' => 'integer', 'description' => 'Default UID for new domains (numeric)'],
            ['key' => 'default_gid', 'value' => '90', 'type' => 'integer', 'description' => 'Default GID for new domains (numeric)'],
            ['key' => 'allow_postmaster_uid_gid', 'value' => 'yes', 'type' => 'string', 'description' => 'Allow postmasters to define their own UID/GID'],
            ['key' => 'siteadmin_manage_domains', 'value' => '1', 'type' => 'integer', 'description' => 'Allow siteadmin user to manage domains'],
            
            // Mail Storage
            ['key' => 'mail_root', 'value' => '/var/vmail/', 'type' => 'string', 'description' => 'Location of mailstore for new domains'],
            ['key' => 'check_mail_root_exists', 'value' => '1', 'type' => 'integer', 'description' => 'Check if mailstore exists when creating domain'],
            
            // Mailman
            ['key' => 'mailman_root', 'value' => 'http://www.EXAMPLE.com/mailman', 'type' => 'string', 'description' => 'Path to Mailman'],
            
            // SpamAssassin
            ['key' => 'spam_tag_threshold', 'value' => '2', 'type' => 'string', 'description' => 'Default SpamAssassin tagging threshold'],
            ['key' => 'spam_refuse_threshold', 'value' => '5', 'type' => 'string', 'description' => 'Default SpamAssassin refuse/drop threshold'],
            
            // Welcome Messages
            ['key' => 'welcome_message', 'value' => "Welcome, {realname} !\n\nYour new E-mail account is all ready for you.\n\nHere are some settings you might find useful:\n\nUsername: {localpart}@{domain}\nPOP3 server: mail.{domain}\nSMTP server: mail.{domain}\n", 'type' => 'string', 'description' => 'Welcome message sent to new POP/IMAP accounts'],
            
            ['key' => 'welcome_new_domain_message', 'value' => "Welcome, and thank you for registering your e-mail domain\n{domain} with us.\n\nIf you have any questions, please\ndon't hesitate to ask your account representative.\n", 'type' => 'string', 'description' => 'Welcome message sent to new domains'],
        ];
        
        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description']
                ]
            );
        }
    }
}