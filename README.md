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

Then run the main install command:

```
php artisan filament-astart:install
```

This will:

- Run all necessary migrations
- Publish configuration and language files
- Publish seeders and stubs
- Seed example roles and permissions
- Setup AAuth and Arflow integrations

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
