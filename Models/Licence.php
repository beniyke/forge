<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * Licence Model represents a software license associated with a product
 * and potentially bound to a client upon activation.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Models;

use Database\BaseModel;
use Database\Query\Builder;
use Database\Traits\HasRefid;
use Forge\Enums\LicenceStatus;
use Helpers\DateTimeHelper;

/**
 * @property int             $id
 * @property string          $refid
 * @property string          $key
 * @property int             $product_id
 * @property ?int            $client_id
 * @property int             $duration_days
 * @property LicenceStatus   $status
 * @property ?DateTimeHelper $activated_at
 * @property ?DateTimeHelper $expires_at
 * @property ?array          $metadata
 * @property ?DateTimeHelper $created_at
 * @property ?DateTimeHelper $updated_at
 *
 * @method static Builder active()
 * @method static Builder pending()
 * @method static Builder expired()
 * @method static Builder revoked()
 * @method static Builder expiringIn(int $days)
 */
class Licence extends BaseModel
{
    use HasRefid;

    protected string $table = 'forge_licence';

    protected array $fillable = [
        'refid',
        'key',
        'product_id',
        'client_id',
        'duration_days',
        'status',
        'activated_at',
        'expires_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected array $casts = [
        'id' => 'integer',
        'product_id' => 'integer',
        'client_id' => 'integer',
        'duration_days' => 'integer',
        'status' => LicenceStatus::class,
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function productId(): int
    {
        return $this->product_id;
    }

    public function clientId(): ?int
    {
        return $this->client_id;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', LicenceStatus::Active)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', DateTimeHelper::now());
            });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', LicenceStatus::Pending);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', LicenceStatus::Expired)
            ->orWhere('expires_at', '<=', DateTimeHelper::now());
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->where('status', LicenceStatus::Revoked);
    }

    public function scopeExpiringIn(Builder $query, int $days): Builder
    {
        return $query->active()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', DateTimeHelper::now()->addDays($days));
    }

    public function isActive(): bool
    {
        if ($this->status !== LicenceStatus::Active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->status === LicenceStatus::Expired || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isPending(): bool
    {
        return $this->status === LicenceStatus::Pending;
    }
}
