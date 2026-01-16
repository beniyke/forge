<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * LicenceStatus Enum defines the possible states for a software license.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Enums;

enum LicenceStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Revoked = 'revoked';

    /**
     * Get a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Activation',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Revoked => 'Revoked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Expired => 'danger',
            self::Revoked => 'secondary',
        };
    }

    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }
}
