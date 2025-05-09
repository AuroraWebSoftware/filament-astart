# Filament Astart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)
[![Total Downloads](https://img.shields.io/packagist/dt/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)

**Filament Astart** is a powerful starter plugin for [FilamentPHP](https://filamentphp.com/), designed to kickstart Laravel admin panels with modular authentication, workflow logic, multilingual support, and prebuilt UI components.

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

Open your Filament panel service provider (usually `AdminPanelProvider`) and register the plugin inside the `panel()` method:

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
### AAuth Seeders
```
php artisan vendor:publish --tag="aauth-seeders"
php artisan db:seed --class=SampleDataSeeder
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

Contributions are welcome! Please read the [CONTRIBUTING](.github/CONTRIBUTING.md) guide before submitting pull requests.

---

## 🛡️ Security

If you discover a security vulnerability, please review [our security policy](../../security/policy) for how to report it.

---

## 🙌 Credits

- [AuroraWebSoftware](https://github.com/AuroraWebSoftware)


---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE.md) for more information.
