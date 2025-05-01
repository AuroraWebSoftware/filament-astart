# This is my package filament-astart

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/aurorawebsoftware/filament-astart/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/aurorawebsoftware/filament-astart/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/aurorawebsoftware/filament-astart/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/aurorawebsoftware/filament-astart/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/aurorawebsoftware/filament-astart.svg?style=flat-square)](https://packagist.org/packages/aurorawebsoftware/filament-astart)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require aurorawebsoftware/filament-astart
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-astart-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-astart-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-astart-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$filamentAstart = new AuroraWebSoftware\FilamentAstart();
echo $filamentAstart->echoPhrase('Hello, AuroraWebSoftware!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [AuroraWebSoftware](https://github.com/AuroraWebSoftware)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
