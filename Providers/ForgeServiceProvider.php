<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * ForgeServiceProvider registers the Forge package services.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Providers;

use Core\Services\ServiceProvider;
use Forge\Services\AnalyticsManagerService;
use Forge\Services\LicenceManagerService;

class ForgeServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->container->singleton(LicenceManagerService::class);
        $this->container->singleton(AnalyticsManagerService::class);
    }
}
