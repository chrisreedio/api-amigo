# API Quota Management and Analytics

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrisreedio/api-amigo.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/api-amigo)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/api-amigo/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/chrisreedio/api-amigo/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/api-amigo/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/chrisreedio/api-amigo/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/chrisreedio/api-amigo.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/api-amigo)


## Overview

API Amigo is a comprehensive Laravel package tailored to elevate your API integration experience. 

It's more than just a quota manager; it's a robust tool for monitoring API health, tracking failed requests, and delivering actionable insights through aggregate statistics. 

API Amigo is your dependable partner, ensuring that you make the most out of every API interaction.

## Key Features

- Quota Monitoring: Stay informed on your API usage with real-time tracking of quota limits. API Amigo diligently monitors quota headers in API responses, ensuring you're always within bounds.
- Failure Analysis: Track and analyze failed API requests. Understand the 'why' behind failures to improve reliability and efficiency in your API interactions.
- Aggregate Statistics: Get a comprehensive view of your API usage with detailed statistics. Understand usage patterns, peak request times, and more, enabling data-driven decisions.
- Intuitive Dashboard: Seamlessly integrated with Laravel's Filament admin panel, API Amigo provides a clear, concise view of your API landscape, all in one place.
- Custom Alerts: Configure alerts for approaching quota limits and detect anomalies in API performance. Stay proactive and prevent unexpected downtimes.
- Easy Integration: Designed with simplicity in mind, API Amigo offers a straightforward setup process, allowing you to focus on building, not configuring.


## Installation

You can install the package via composer:

```bash
composer require chrisreedio/api-amigo
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="api-amigo-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="api-amigo-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="api-amigo-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$aPIAmigo = new ChrisReedIO\APIAmigo();
echo $aPIAmigo->echoPhrase('Hello, ChrisReedIO!');
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

- [Chris Reed](https://github.com/chrisreedio)
- [All Contributors](../../contributors)

## License

This is currently closed source.

License choice is pending. Please check back soon.
