<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * LicenceBuilder provides a fluent interface for generating software licenses.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Services\Builders;

use Forge\Enums\LicenceStatus;
use Forge\Models\Licence;
use Forge\Services\LicenceManagerService;

class LicenceBuilder
{
    protected array $data = [];

    public function key(string $key): self
    {
        $this->data['key'] = $key;

        return $this;
    }

    public function product(mixed $product): self
    {
        if (is_object($product)) {
            $this->data['product_id'] = $product->id;
        } else {
            $this->data['product_id'] = $product;
        }

        return $this;
    }

    public function client(mixed $client): self
    {
        if (is_object($client)) {
            $this->data['client_id'] = $client->id;
        } else {
            $this->data['client_id'] = $client;
        }

        return $this;
    }

    public function duration(int $days): self
    {
        $this->data['duration_days'] = $days;

        return $this;
    }

    public function status(LicenceStatus|string $status): self
    {
        $this->data['status'] = $status;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $this->data['metadata'] = array_merge($this->data['metadata'] ?? [], $metadata);

        return $this;
    }

    public function create(): Licence
    {
        return resolve(LicenceManagerService::class)->create($this->data);
    }
}
