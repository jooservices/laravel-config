# Quick Start

```php
use JOOservices\LaravelConfig\Facades\Config;

Config::set('system.site_name', 'XCrawler');
Config::set('system.enabled', true);
Config::set('payment.retry_times', 3);

$siteName = Config::get('system.site_name', 'Default');
$payment = Config::group('payment');
$all = Config::all();
```

## Useful operator commands

```bash
php artisan config-store:get system.site_name --default="Default"
php artisan config-store:set system.enabled true --type=bool
php artisan config-store:refresh
```
