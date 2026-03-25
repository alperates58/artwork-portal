<?php

namespace App\Services;

use App\Models\PurchaseOrder;

class MailNotificationService
{
    public function __construct(private PortalSettings $settings) {}

    public function isEnabled(): bool
    {
        return (bool) ($this->settings->mailNotificationConfig()['enabled'] ?? false);
    }

    public function newOrderRecipients(): array
    {
        $config = $this->settings->mailNotificationConfig();

        return [
            'to' => $this->parseRecipientList($config['graphics_to'] ?? null),
            'cc' => $this->parseRecipientList($config['graphics_cc'] ?? null),
            'bcc' => $this->parseRecipientList($config['graphics_bcc'] ?? null),
        ];
    }

    public function hasNewOrderRecipients(): bool
    {
        return $this->newOrderRecipients()['to'] !== [];
    }

    public function newOrderSubject(PurchaseOrder $order): string
    {
        $template = (string) ($this->settings->mailNotificationConfig()['new_order_subject'] ?? '');

        if (blank($template)) {
            $template = 'Yeni siparis geldi: {order_no}';
        }

        return strtr($template, [
            '{order_no}' => (string) $order->order_no,
            '{supplier}' => (string) ($order->supplier?->name ?? ''),
            '{order_date}' => optional($order->order_date)->format('d.m.Y') ?? '',
            '{line_count}' => (string) ($order->lines_count ?? $order->lines()->count()),
        ]);
    }

    public function fromOverride(): ?array
    {
        $config = $this->settings->mailNotificationConfig();
        $address = $config['override_from_address'] ?? null;
        $name = $config['override_from_name'] ?? null;

        if (blank($address)) {
            return null;
        }

        return [
            'address' => (string) $address,
            'name' => filled($name) ? (string) $name : (string) config('mail.from.name'),
        ];
    }

    public function testRecipient(?string $fallback = null): ?string
    {
        $configured = $this->settings->mailNotificationConfig()['test_recipient'] ?? null;

        foreach ([$configured, $fallback] as $candidate) {
            if (filled($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return (string) $candidate;
            }
        }

        return null;
    }

    public function parseRecipientList(null|string|array $value): array
    {
        $items = is_array($value)
            ? $value
            : preg_split('/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);

        return collect($items)
            ->map(fn ($email) => trim((string) $email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();
    }
}
