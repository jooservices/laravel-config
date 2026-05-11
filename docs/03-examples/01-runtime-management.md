# Runtime Management Example

```php
use JOOservices\LaravelConfig\Facades\Config;

Config::set('system.maintenance', true, 'bool');

if (Config::get('system.maintenance', false)) {
    // apply maintenance behavior in your application boundary
}

Config::refresh();
```

## Operational note

If you run queue workers or long-lived PHP processes, call `refresh()` after out-of-band config changes or recycle the worker so it does not keep stale in-memory state.
