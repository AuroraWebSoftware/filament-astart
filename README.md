# Filament Astart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)
[![Total Downloads](https://img.shields.io/packagist/dt/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)

**Filament Astart** is a powerful starter plugin for [FilamentPHP](https://filamentphp.com/), designed to kickstart
Laravel admin panels with modular authentication, workflow logic, multilingual support, and prebuilt UI components.

---

## 📦 Included Dependencies

This package relies on the following AuroraWebSoftware components:

- 🛡️ [**AAuth**](https://github.com/AuroraWebSoftware/AAuth): Advanced authentication and role-permission management.


- 🔄 [**Arflow**](https://github.com/AuroraWebSoftware/Arflow): Workflow engine for dynamic state transitions.

---

## 🚀 Installation

Install the package via Composer:

```
composer require aurorawebsoftware/filament-astart
```

Then run the main installation command:

```
php artisan filament-astart:install
```

> ⚠️ **Warning:** This is a first-time installation command.
>
> It will automatically **publish and overwrite** configuration, language, and stub files using the `--force` flag.
>
> Make sure to backup or version control your custom changes before running.


This will:

- Run all necessary migrations
- Publish configuration and language files
- Publish seeders and stubs
- Seed example roles and permissions
- Setup AAuth and Arflow integrations

### 📥 Post-Installation Setup

After running the installation command, make sure to complete the following steps:

#### 1️⃣ Register the plugin in your Filament panel provider

Open your Filament panel service provider (usually `AdminPanelProvider`) and register the plugin inside the `panel()`
method:

```php
use AuroraWebSoftware\FilamentAstart\FilamentAstartPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            FilamentAstartPlugin::make(),
        ]);
}
```

#### 2️⃣ Update your User model

Your `User` model must implement the required contract and trait from the AAuth package:

```php
use AuroraWebSoftware\AAuth\Traits\AAuthUser;
use AuroraWebSoftware\AAuth\Contracts\AAuthUserContract;

class User extends Authenticatable implements AAuthUserContract
{
    use AAuthUser;

    // Your user model logic...
}
```

This ensures that AAuth can interact properly with your authenticated users.

### 🔐 Default Credentials

After installation, you can log in with the following default user credentials (if you seeded the sample data):

```text
Email:    user1@example.com
Password: password
```

> ⚠️ **Important:** Be sure to change or delete this user in production environments.


---

## 🎨 Features

### Modern User Menu

The plugin provides an enhanced user dropdown menu with avatar, role badges, organization node display, and a theme switcher. Configurable via `config/filament-astart.php`:

```php
'user_menu_style' => env('ASTART_USER_MENU_STYLE', 'classic'),
// 'classic' = Default Filament user menu
// 'modern'  = Enhanced menu with avatar, role badge, org node, theme switcher
```

The modern menu fully supports Filament's `userMenuItems()` API — any items registered via `$panel->userMenuItems([...])` will automatically appear in the modern menu.

### LogiAudit Integration (Optional)

Built-in read-only pages for [LogiAudit](https://github.com/AuroraWebSoftware/LogiAudit) log viewing and change history. **No composer dependency required** — pages only appear when the LogiAudit package is installed.

- **System Logs** — Filterable log viewer with level badges, tag support, date range filters, and a stats widget (colored cards for errors, warnings, info)
- **Change History** — Model change tracking with old/new value comparison table

Both pages require AAuth permissions to be granted. Configure in `config/astart-auth.php`:

```php
'LogiAuditLog' => ['view', 'view_any'],
'LogiAuditHistory' => ['view', 'view_any'],
```

Resource visibility can also be toggled in `config/filament-astart.php`:

```php
'logiaudit_log' => ['active' => true, 'navigation_group_key' => null],
'logiaudit_history' => ['active' => true, 'navigation_group_key' => null],
```

> Backward compatible with older LogiAudit versions — columns like `tag` and `causer_type` are checked at runtime.

### User Active/Passive Toggle

Instead of deleting users (which causes orphan records in related tables), users can be activated or deactivated via a toggle button on the edit page. Requires an `is_active` column on the users table.

### Avatar Support

Optional avatar upload and display throughout the plugin. Enable in config:

```php
'avatar' => [
    'enabled' => env('ASTART_AVATAR_ENABLED', false),
],
```

When enabled, adds avatar upload to user form, displays avatars in user view, user menu, and select components.

### UserSelect / UserMultiSelect Components

Reusable form components for selecting users with optional avatar display:

```php
use AuroraWebSoftware\FilamentAstart\Forms\Components\UserSelect;
use AuroraWebSoftware\FilamentAstart\Forms\Components\UserMultiSelect;

UserSelect::make('user_id'),
UserMultiSelect::make('user_ids'),
```

---

## ⚙️ Manual Publish Options

You may publish each resource manually if needed:

### Config File

```
php artisan vendor:publish --tag="filament-astart-config"
```

### Language Files

```
php artisan vendor:publish --tag="filament-astart-lang"
```

### Seeders

```
php artisan vendor:publish --tag=filament-astart-seeders
php artisan db:seed --class=SampleFilamentDataSeeder
```

### Arflow Config

```
php artisan vendor:publish --tag=arflow-config
```

---

## 📘 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## 🤝 Contributing

Contributions are welcome! Please read the [CONTRIBUTING](.github/CONTRIBUTING.md) guide before submitting pull
requests.

---

## 🛡️ Security

If you discover a security vulnerability, please review [our security policy](../../security/policy) for how to report
it.

---

## 🙌 Credits

- [AuroraWebSoftware](https://github.com/AuroraWebSoftware)

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.
