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
php artisan config-store:ensure-index
php artisan config-store:get system.site_name --default="Default"
php artisan config-store:set system.enabled true --type=bool
php artisan config-store:refresh
```

Run `config-store:ensure-index` before first writes to ensure the `group` + `key` unique index exists in MongoDB.
