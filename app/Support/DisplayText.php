<?php

namespace App\Support;

class DisplayText
{
    private const MOJIBAKE_MAP = [
        'ÃƒÂ‡' => 'Ç',
        'Ãƒâ€¡' => 'Ç',
        'ÃƒÂ§' => 'ç',
        'ÃƒÂœ' => 'Ü',
        'ÃƒÅ“' => 'Ü',
        'ÃƒÂ¼' => 'ü',
        'Ãƒâ€“' => 'Ö',
        'Ãƒâ€“' => 'Ö',
        'ÃƒÂ¶' => 'ö',
        'Ã„Â°' => 'İ',
        'Ã„Â±' => 'ı',
        'Ã„Å¸' => 'Ğ',
        'Ã„ÅŸ' => 'ğ',
        'Ã…Å¾' => 'Ş',
        'Ã…Å¸' => 'ş',
        'Ã‡' => 'Ç',
        'Ã§' => 'ç',
        'Ãœ' => 'Ü',
        'Ã¼' => 'ü',
        'Ã–' => 'Ö',
        'Ã¶' => 'ö',
        'Ä°' => 'İ',
        'Ä±' => 'ı',
        'Äž' => 'Ğ',
        'ÄŸ' => 'ğ',
        'Åž' => 'Ş',
        'ÅŸ' => 'ş',
        'Â·' => '·',
        'Â' => '',
        'â€™' => '\'',
        'â€˜' => '\'',
        'â€œ' => '"',
        'â€' => '"',
        'â€“' => '-',
        'â€”' => '-',
    ];

    public static function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim(str_replace("\u{00A0}", ' ', $value));

        if ($value === '') {
            return '';
        }

        if (self::looksMisencoded($value)) {
            for ($i = 0; $i < 3 && self::looksMisencoded($value); $i++) {
                $normalized = strtr($value, self::MOJIBAKE_MAP);

                if ($normalized === $value) {
                    break;
                }

                $value = $normalized;
            }
        }

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private static function looksMisencoded(string $value): bool
    {
        return preg_match('/Ã.|Å.|Ä.|Â.|â./u', $value) === 1;
    }
}
