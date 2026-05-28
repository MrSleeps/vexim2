php artisan make:filament-user

php artisan tinker
>>> Spatie\Permission\Models\Role::create(['name' => 'super-admin']);

php artisan fin-mail:install <-- answer no to the migrations


php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=SettingsSeeder