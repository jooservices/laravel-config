<?php

declare(strict_types=1);

namespace JOOservices\LaravelConfig\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use JOOservices\LaravelConfig\Contracts\ConfigStore;
use JOOservices\LaravelConfig\Models\Config as ConfigModel;
use JOOservices\LaravelConfig\Support\ConfigType;
use Throwable;

class DoctorConfigCommand extends Command
{
    protected $signature = 'config-store:doctor';

    protected $description = 'Check MongoDB connectivity, indexes, cache, and config document health.';

    public function handle(ConfigStore $configService, CacheFactory $cacheFactory): int
    {
        $checks = [
            'MongoDB connectivity' => $this->checkMongoConnectivity(),
            'Unique group/key index' => $this->checkIndex($configService),
            'Cache round-trip' => $this->checkCacheRoundTrip($cacheFactory),
            'Unknown config types' => $this->checkUnknownTypes(),
        ];

        $failed = false;

        foreach ($checks as $label => $passed) {
            if ($passed) {
                $this->info('[ok] '.$label);
            } else {
                $failed = true;
                $this->error('[fail] '.$label);
            }
        }

        if ($failed) {
            $this->error('Config store doctor found issues.');

            return self::FAILURE;
        }

        $this->info('Config store doctor passed all checks.');

        return self::SUCCESS;
    }

    protected function checkMongoConnectivity(): bool
    {
        try {
            ConfigModel::query()->limit(1)->get();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function checkIndex(ConfigStore $configService): bool
    {
        try {
            $configService->ensureIndexes();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function checkCacheRoundTrip(CacheFactory $cacheFactory): bool
    {
        if (! (bool) config('config-store.cache_enabled', true)) {
            return true;
        }

        try {
            $configuredStore = config('config-store.cache_store');
            $defaultStore = config('cache.default', 'array');
            $storeName = is_string($configuredStore) && $configuredStore !== ''
                ? $configuredStore
                : (is_string($defaultStore) ? $defaultStore : 'array');
            $cache = $cacheFactory->store($storeName);
            $key = 'config_store_doctor_'.uniqid('', true);
            $cache->put($key, 'ok', 60);

            return $cache->get($key) === 'ok';
        } catch (Throwable) {
            return false;
        }
    }

    protected function checkUnknownTypes(): bool
    {
        $supported = ConfigType::supportedValues();

        try {
            foreach (ConfigModel::query()->get(['type']) as $record) {
                $type = (string) ($record->type ?? '');

                if ($type !== '' && ! in_array($type, $supported, true)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
