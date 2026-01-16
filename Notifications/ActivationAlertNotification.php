<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * ActivationAlertNotification is sent to the client when a license is successfully activated.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Notifications;

use Mail\Core\EmailComponent;
use Mail\EmailNotification;

class ActivationAlertNotification extends EmailNotification
{
    public function getRecipients(): array
    {
        return [
            'to' => [
                $this->payload->get('email') => $this->payload->get('name'),
            ],
        ];
    }

    public function getSubject(): string
    {
        return "License Activated Successfully";
    }

    public function getTitle(): string
    {
        return "License Activated";
    }

    protected function getRawMessageContent(): string
    {
        $name = $this->payload->get('name');
        $key = $this->payload->get('key');
        $expiresAt = $this->payload->get('expires_at');
        $productName = $this->payload->get('product_name');

        return EmailComponent::make()
            ->status('Your license is now active.', 'success')
            ->greeting("Hello {$name},")
            ->line("Your license **{$key}** has been successfully activated.")
            ->attributes([
                'License Key' => $key,
                'Expires At' => $expiresAt,
                'Product' => $productName,
            ])
            ->action('Manage License', url($this->payload->get('manage_url')))
            ->render();
    }
}
