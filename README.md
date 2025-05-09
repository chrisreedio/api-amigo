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

Install the plugin into your panel by adding the following to your `AdminPanelProvider.php` (or other panel) file:

```php
    ->plugins([
        // ... Other plugins
        \ChrisReedIO\APIAmigo\APIAmigoPlugin::make(),
    ])
```

Be sure to enable API Amigo tracking globally via your `.env`:
```dotenv
AMIGO_ENABLED=true
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="api-amigo-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="api-amigo-views"
```

## Response Aggregation

API Amigo provides powerful commands to aggregate API response data for analytics and reporting. These commands help you track performance metrics, success rates, and usage patterns over time.

### Available Commands

1. **Daily Aggregation**
   ```bash
   php artisan responses:aggregate-daily
   ```
   This command processes the previous day's responses and creates hourly aggregations. It's designed to be run daily via the scheduler.

2. **Historical Data Processing**
   ```bash
   # Process all historical data up to yesterday
   php artisan responses:aggregate --all

   # Process a specific date range
   php artisan responses:aggregate 2023-01-01 2023-12-31 --bulk

   # Process a single day
   php artisan responses:aggregate 2023-01-01
   ```

3. **Pruning Old Responses**
   ```bash
   # Prune responses older than the configured lifetime (default: 90 days)
   php artisan responses:prune

   # Force prune without confirmation
   php artisan responses:prune --force
   ```

### Setting Up Scheduling

For Laravel 11/12 applications, add the following to your `app/Console/Kernel.php`:

```php
use Illuminate\Support\Facades\Schedule;
use ChrisReedIO\APIAmigo\Commands\ResponsesDailyAggregationCommand;
use ChrisReedIO\APIAmigo\Commands\PruneResponsesCommand;

protected function schedule(Schedule $schedule): void
{
    // Run daily aggregation at 1 AM in your configured timezone
    Schedule::command(new ResponsesDailyAggregationCommand)
        ->dailyAt('22:00')
        ->timezone(config('api-amigo.aggregation.timezone', 'UTC'));

    // Run pruning weekly on Sunday at 2 AM
    Schedule::command(new PruneResponsesCommand, ['--force'])
        ->weekly()
        ->sundays()
        ->at('02:00');
}
```

### Configuration

You can configure the timezone and response lifetime in your `.env` file:
```dotenv
AMIGO_AGGREGATION_TIMEZONE=America/New_York
AMIGO_PRUNE_LIFETIME_DAYS=90
```

Or directly in `config/api-amigo.php`:
```php
'aggregation' => [
    'timezone' => 'America/New_York',
    'prune_lifetime_days' => 90,
],
```

### Initial Setup

When first installing API Amigo or after a long period without aggregation, you should:

1. Process all historical data:
   ```bash
   php artisan responses:aggregate --all
   ```

2. Set up the daily scheduler as shown above

The `--all` command will:
- Find your earliest response date
- Process all data up to yesterday
- Use bulk processing to handle large datasets efficiently
- Show progress and statistics during processing

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
