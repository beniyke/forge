<?php

declare(strict_types=1);

/**
 * Anchor Framework
 *
 * ExpirationWarningNotification is sent to the client when a license is about to expire.
 *
 * @author BenIyke <beniyke34@gmail.com> | Twitter: @BigBeniyke
 */

namespace Forge\Notifications;

use Mail\Core\EmailComponent;
use Mail\EmailNotification;

class ExpirationWarningNotification extends EmailNotification
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
        return "License Expiration Warning";
    }

    public function getTitle(): string
    {
        return "Action Required: License Expiring Soon";
    }

    protected function getRawMessageContent(): string
    {
        $name = $this->payload->get('name');
        $key = $this->payload->get('key');
        $expiresAt = $this->payload->get('expires_at');
        $daysLeft = $this->payload->get('days_left');
        $licenceId = $this->payload->get('licence_id');

        return EmailComponent::make()
            ->status("Your license expires in {$daysLeft} days.", 'warning')
            ->greeting("Hello {$name},")
            ->line("This is a reminder that your license **{$key}** is set to expire on **{$expiresAt}**.")
            ->line("Please renew your license to avoid any service interruption.")
            ->action('Renew Now', url($this->payload->get('renew_url') . '/' . $licenceId))
            ->render();
    }
}
