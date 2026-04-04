<?php

namespace App\Services;

use App\Models\SupplierRegistration;
use App\Models\User;

class SupplierRegistrationMailService
{
    public function __construct(
        private PortalSettings $settings,
    ) {}

    public function config(): array
    {
        return $this->settings->supplierRegistrationMailConfig();
    }

    public function definitions(): array
    {
        return $this->settings->supplierRegistrationMailDefinitions();
    }

    public function eventIsEnabled(string $event): bool
    {
        return (bool) data_get($this->config(), "events.{$event}.enabled", false);
    }

    public function subjectFor(string $event, SupplierRegistration $registration, ?User $user = null): string
    {
        $definitions = $this->definitions();
        $template = (string) data_get(
            $this->config(),
            "events.{$event}.subject",
            data_get($definitions, "{$event}.subject_default", '')
        );

        if (blank($template)) {
            $template = (string) data_get($definitions, "{$event}.subject_default", '');
        }

        return $this->renderTemplate($template, $this->replacements($registration, $user));
    }

    public function bodyHtmlFor(string $event, SupplierRegistration $registration, ?User $user = null): string
    {
        $definitions = $this->definitions();
        $template = (string) data_get(
            $this->config(),
            "events.{$event}.body",
            data_get($definitions, "{$event}.body_default", '')
        );

        if (blank($template)) {
            $template = (string) data_get($definitions, "{$event}.body_default", '');
        }

        $rendered = $this->renderTemplate($template, $this->replacements($registration, $user));

        return $this->formatBodyAsHtml($rendered);
    }

    public function recipientFor(SupplierRegistration $registration, ?User $user = null): ?string
    {
        $candidate = $user?->email ?: $registration->company_email;

        if (! filled($candidate) || ! filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return (string) $candidate;
    }

    private function replacements(SupplierRegistration $registration, ?User $user = null): array
    {
        $reviewerName = $registration->reviewedBy?->name;

        return [
            'kayit_user' => (string) ($user?->name ?: $registration->contact_name),
            'firma_adi' => (string) $registration->company_name,
            'kayit_email' => (string) ($user?->email ?: $registration->company_email),
            'portal_adi' => (string) config('portal.brand_name'),
            'login_url' => route('login'),
            'kayit_tarihi' => optional($registration->created_at)->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i') ?? '',
            'onay_tarihi' => optional($registration->reviewed_at)->timezone(config('app.timezone', 'Europe/Istanbul'))->format('d.m.Y H:i') ?? '',
            'onaylayan_kullanici' => (string) ($reviewerName ?: ''),
        ];
    }

    private function renderTemplate(string $template, array $replacements): string
    {
        $rendered = $template;

        foreach ($replacements as $token => $value) {
            $stringValue = (string) $value;
            $rendered = strtr($rendered, [
                '{{' . $token . '}}' => $stringValue,
                '{' . $token . '}' => $stringValue,
            ]);

            $rendered = preg_replace_callback(
                '/(?<![A-Za-z0-9_])' . preg_quote($token, '/') . '(?![A-Za-z0-9_])/',
                static fn () => $stringValue,
                $rendered
            ) ?? $rendered;
        }

        return $rendered;
    }

    private function formatBodyAsHtml(string $body): string
    {
        $normalized = trim(preg_replace("/\r\n?/", "\n", $body) ?? $body);

        if ($normalized === '') {
            return '';
        }

        $paragraphs = preg_split("/\n{2,}/", $normalized) ?: [];

        return collect($paragraphs)
            ->map(function (string $paragraph): string {
                return '<p>' . nl2br(e(trim($paragraph))) . '</p>';
            })
            ->implode("\n");
    }
}
