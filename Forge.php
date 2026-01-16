<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Forge Facade provides a static interface for license operations.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge;

use Forge\Models\Licence;
use Forge\Services\AnalyticsManagerService;
use Forge\Services\Builders\LicenceBuilder;
use Forge\Services\LicenceManagerService;

class Forge
{
    /**
     * Start a fluent license generation builder.
     */
    public static function make(): LicenceBuilder
    {
        return resolve(LicenceManagerService::class)->make();
    }

    /**
     * Activate a license for a client.
     */
    public static function activate(Licence|int|string $licence, mixed $client): bool
    {
        return resolve(LicenceManagerService::class)->activate($licence, $client);
    }

    public static function analytics(): AnalyticsManagerService
    {
        return resolve(AnalyticsManagerService::class);
    }

    public static function verify(string $key): ?Licence
    {
        return resolve(LicenceManagerService::class)->verify($key);
    }

    public static function findByRefid(string $refid): ?Licence
    {
        return resolve(LicenceManagerService::class)->findByRefid($refid);
    }

    public static function revoke(Licence|int|string $licence): bool
    {
        return resolve(LicenceManagerService::class)->revoke($licence);
    }

    /**
     * Forward static calls to LicenceManagerService.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return resolve(LicenceManagerService::class)->$method(...$arguments);
    }
}
