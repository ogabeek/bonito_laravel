<?php

namespace App\Support;

/**
 * Curated list of common spoken languages (ISO 639-1 code => English name).
 *
 * This is the student's native/spoken language, tracked separately from their
 * country since the two often differ. Curated rather than exhaustive: it covers
 * the languages our students actually speak — extend as needed.
 */
class Languages
{
    /** @var array<string, string> */
    private const NAMES = [
        'ar' => 'Arabic',
        'bn' => 'Bengali',
        'bg' => 'Bulgarian',
        'zh' => 'Chinese',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en' => 'English',
        'et' => 'Estonian',
        'fi' => 'Finnish',
        'fr' => 'French',
        'ka' => 'Georgian',
        'de' => 'German',
        'el' => 'Greek',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'id' => 'Indonesian',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'kk' => 'Kazakh',
        'ko' => 'Korean',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'ms' => 'Malay',
        'no' => 'Norwegian',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sr' => 'Serbian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        'vi' => 'Vietnamese',
    ];

    /** @return array<string, string> */
    public static function all(): array
    {
        return self::NAMES;
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::NAMES);
    }

    public static function has(?string $code): bool
    {
        return $code !== null && isset(self::NAMES[strtolower($code)]);
    }

    public static function name(?string $code): ?string
    {
        return $code === null ? null : (self::NAMES[strtolower($code)] ?? null);
    }

    /** Same shape as the map; provided for symmetry with Countries::options(). */
    public static function options(): array
    {
        return self::NAMES;
    }
}
