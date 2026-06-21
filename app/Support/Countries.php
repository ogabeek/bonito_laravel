<?php

namespace App\Support;

/**
 * ISO 3166-1 alpha-2 country reference data.
 *
 * The code => name map lives in data/countries.php, generated from ICU
 * (ext-intl) region data so we neither hand-maintain ~270 names nor depend on
 * ext-intl being present in production. To regenerate after an ICU update:
 *
 *   $exclude = ['EU','EZ','QO','UN','ZZ','XA','XB'];
 *   foreach two-letter codes: name = Locale::getDisplayRegion("-{$code}", 'en')
 *   keep when name !== code and not "Unknown", sort by name, dump to data file.
 *
 * Flags are derived from the code (regional-indicator codepoints), never stored.
 */
class Countries
{
    /** @var array<string, string>|null */
    private static ?array $names = null;

    /** @return array<string, string> code => English name */
    public static function all(): array
    {
        return self::$names ??= require __DIR__.'/data/countries.php';
    }

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function has(?string $code): bool
    {
        return $code !== null && isset(self::all()[strtoupper($code)]);
    }

    public static function name(?string $code): ?string
    {
        return $code === null ? null : (self::all()[strtoupper($code)] ?? null);
    }

    /**
     * Flag emoji for an alpha-2 code, built from Unicode regional indicators.
     * Returns '' for unknown codes so callers can render nothing.
     */
    public static function flag(?string $code): string
    {
        if (! self::has($code)) {
            return '';
        }

        $code = strtoupper($code);

        // 0x1F1E6 ('A') - ord('A') = 127397
        return mb_chr(127397 + ord($code[0])).mb_chr(127397 + ord($code[1]));
    }

    /**
     * Options for a <select>: code => "🇪🇸 Spain", ordered by country name.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::all() as $code => $name) {
            $options[$code] = self::flag($code).' '.$name;
        }

        return $options;
    }
}
