<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * LicenceManagerService handles the generation, activation, and verification of licenses.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Services;

use Client\Client;
use Database\Exceptions\ValidationException;
use Forge\Enums\LicenceStatus;
use Forge\Exceptions\ForgeException;
use Forge\Exceptions\LicenseActivationException;
use Forge\Models\Licence;
use Forge\Notifications\ActivationAlertNotification;
use Forge\Services\Builders\LicenceBuilder;
use Helpers\Data;
use Helpers\DateTimeHelper;
use Helpers\Validation\Validator;
use Mail\Mail;
use Wave\Wave;

class LicenceManagerService
{
    public function make(): LicenceBuilder
    {
        return new LicenceBuilder();
    }

    /** @throws ValidationException */
    public function create(array $data): Licence
    {
        $validator = (new Validator())->rules([
            'product_id' => ['required' => true, 'type' => 'integer', 'exist' => 'wave_product.id'],
            'duration_days' => ['required' => false, 'type' => 'integer', 'limit' => '1'],
        ])->validate($data);

        if ($validator->has_error()) {
            throw new ValidationException("License creation failed.", $validator->errors());
        }

        if (! isset($data['status'])) {
            $data['status'] = LicenceStatus::Pending;
        }

        if (! isset($data['key'])) {
            $data['key'] = $this->generateKey();
        }

        return Licence::create($data);
    }

    protected function generateKey(): string
    {
        return strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
    }

    public function activate(Licence|int|string $licence, mixed $client): bool
    {
        if (! $licence instanceof Licence) {
            $licence = Licence::find($licence);
        }

        if (! $licence) {
            throw new ForgeException("License not found.");
        }

        if (is_object($client)) {
            $clientId = $client->id;
            $clientModel = $client;
        } else {
            $clientId = (int) $client;
            $clientModel = Client::find($clientId);
        }

        $validator = (new Validator())->rules([
            'client_id' => ['required' => true, 'exist' => 'client.id'],
        ])->validate(['client_id' => $clientId]);

        if ($validator->has_error()) {
            throw new ValidationException("License activation failed.", $validator->errors());
        }

        if (! $licence->isPending()) {
            throw new LicenseActivationException("License is not in pending state.");
        }

        $now = DateTimeHelper::now();
        $expiresAt = $licence->duration_days ? $now->copy()->addDays($licence->duration_days) : null;

        $updated = $licence->update([
            'client_id' => $clientId,
            'status' => LicenceStatus::Active,
            'activated_at' => $now,
            'expires_at' => $expiresAt,
        ]);

        if ($updated) {
            if ($clientModel && $clientModel->email) {
                Mail::send(new ActivationAlertNotification(Data::make([
                    'name' => $clientModel->name,
                    'email' => $clientModel->email,
                    'key' => $licence->key,
                    'expires_at' => $licence->expires_at ? $licence->expires_at->format('Y-m-d') : 'Never',
                    'product_name' => Wave::findProduct($licence->product_id)->name ?? 'N/A',
                    'manage_url' => config('forge.urls.manage', 'client/licenses'),
                ])));
            }
        }

        return $updated;
    }

    public function verify(string $key): ?Licence
    {
        $licence = Licence::query()->where('key', $key)->first();

        if (! $licence || ! $licence->isActive()) {
            return null;
        }

        return $licence;
    }

    public function findByRefid(string $refid): ?Licence
    {
        return Licence::query()->where('refid', $refid)->first();
    }

    public function revoke(Licence|int|string $licence): bool
    {
        if (! $licence instanceof Licence) {
            $licence = Licence::find($licence);
        }

        if (! $licence) {
            return false;
        }

        return $licence->update(['status' => LicenceStatus::Revoked]);
    }
}
