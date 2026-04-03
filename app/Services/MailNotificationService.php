<?php

namespace App\Services;

use App\Models\ArtworkRevision;
use App\Models\PurchaseOrder;
use App\Models\User;

class MailNotificationService
{
    public function __construct(private PortalSettings $settings) {}

    public function isEnabled(): bool
    {
        return (bool) ($this->settings->mailNotificationConfig()['enabled'] ?? false);
    }

    public function newOrderRecipients(): array
    {
        return $this->eventRecipients('new_order');
    }

    public function hasNewOrderRecipients(): bool
    {
        return $this->hasEventRecipients('new_order');
    }

    public function artworkUploadedRecipients(): array
    {
        return $this->eventRecipients('artwork_uploaded');
    }

    public function hasArtworkUploadedRecipients(): bool
    {
        return $this->hasEventRecipients('artwork_uploaded');
    }

    public function newOrderSubject(PurchaseOrder $order): string
    {
        return $this->renderEventSubject('new_order', [
            '{order_no}' => (string) $order->order_no,
            '{supplier}' => (string) ($order->supplier?->name ?? ''),
            '{order_date}' => optional($order->order_date)->format('d.m.Y') ?? '',
            '{line_count}' => (string) ($order->lines_count ?? $order->lines()->count()),
        ]);
    }

    public function artworkUploadedSubject(ArtworkRevision $revision): string
    {
        $order = $revision->artwork->orderLine->purchaseOrder;
        $line = $revision->artwork->orderLine;

        return $this->renderEventSubject('artwork_uploaded', [
            '{order_no}' => (string) ($order?->order_no ?? ''),
            '{supplier}' => (string) ($order?->supplier?->name ?? ''),
            '{product_code}' => (string) ($line?->product_code ?? ''),
            '{revision_no}' => (string) $revision->revision_no,
            '{uploaded_by}' => (string) ($revision->uploadedBy?->name ?? ''),
        ]);
    }

    public function eventIsEnabled(string $event): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        return (bool) data_get($this->settings->mailNotificationConfig(), "events.{$event}.enabled", false);
    }

    public function eventRecipients(string $event): array
    {
        $config = data_get($this->settings->mailNotificationConfig(), "events.{$event}", []);
        $departmentIds = collect($config['department_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $departmentRecipients = $this->departmentRecipients($departmentIds);

        return [
            'to' => collect($departmentRecipients)
                ->merge($this->parseRecipientList($config['to'] ?? null))
                ->unique()
                ->values()
                ->all(),
            'cc' => $this->parseRecipientList($config['cc'] ?? null),
            'bcc' => $this->parseRecipientList($config['bcc'] ?? null),
        ];
    }

    public function hasEventRecipients(string $event): bool
    {
        return $this->eventRecipients($event)['to'] !== [];
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

    private function renderEventSubject(string $event, array $replacements): string
    {
        $definitions = $this->settings->mailNotificationEventDefinitions();
        $template = (string) data_get(
            $this->settings->mailNotificationConfig(),
            "events.{$event}.subject",
            $definitions[$event]['subject_default'] ?? ''
        );

        if (blank($template)) {
            $template = (string) ($definitions[$event]['subject_default'] ?? '');
        }

        return strtr($template, $replacements);
    }

    private function departmentRecipients(array $departmentIds): array
    {
        if ($departmentIds === []) {
            return [];
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'purchasing', 'graphic'])
            ->whereIn('department_id', $departmentIds)
            ->pluck('email')
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => (string) $email)
            ->unique()
            ->values()
            ->all();
    }
}
