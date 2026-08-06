<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Support;

final class SqliteCaseFolding
{
    private const MAX_CACHED_CHARACTERS = 512;

    /**
     * Reverse Unicode case-fold equivalence classes that cannot be discovered by
     * repeatedly applying lower, upper, title, and full case folding to one member.
     *
     * Generated with tools/generate-sqlite-case-equivalents.php against Unicode
     * 15.1 case-folding data exposed by PHP's intl/mbstring runtime. The source
     * semantics are Unicode CaseFolding.txt; regenerate this table when the
     * supported runtime moves to a newer Unicode version:
     * https://www.unicode.org/Public/UCD/latest/ucd/CaseFolding.txt
     *
     * @var array<string, string>
     */
    private const REVERSE_EQUIVALENTS = [
        'k' => 'KkK',
        's' => 'Ssſ',
        'ss' => 'ßẞ',
        'st' => 'ﬅﬆ',
        'å' => 'ÅåÅ',
        'β' => 'Ββϐ',
        'ε' => 'Εεϵ',
        'θ' => 'Θθϑϴ',
        'ι' => 'ͅΙιι',
        'ΐ' => 'ΐΐ',
        'κ' => 'Κκϰ',
        'μ' => 'µΜμ',
        'π' => 'Ππϖ',
        'ρ' => 'Ρρϱ',
        'σ' => 'Σςσ',
        'ΰ' => 'ΰΰ',
        'φ' => 'Φφϕ',
        'ω' => 'ΩωΩ',
        'в' => 'Ввᲀ',
        'д' => 'Ддᲁ',
        'о' => 'Ооᲂ',
        'с' => 'Ссᲃ',
        'т' => 'Ттᲄᲅ',
        'ъ' => 'Ъъᲆ',
        'ѣ' => 'Ѣѣᲇ',
        'ṡ' => 'Ṡṡẛ',
        'ꙋ' => 'ᲈꙊꙋ',
    ];

    public static function globPattern(string $value, bool $literal): string
    {
        $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) {
            return self::escapeGlob($value);
        }

        $pattern = '';
        foreach ($characters as $character) {
            if (! $literal && $character === '%') {
                $pattern .= '*';

                continue;
            }

            if (! $literal && $character === '_') {
                $pattern .= '?';

                continue;
            }

            $variants = self::caseVariants($character);
            $pattern .= count($variants) > 1
                ? '['.implode('', $variants).']'
                : self::escapeGlob($character);
        }

        return $pattern;
    }

    public static function escapeGlob(string $value): string
    {
        return str_replace(['[', '*', '?'], ['[[]', '[*]', '[?]'], $value);
    }

    /** @return list<string> */
    private static function caseVariants(string $character): array
    {
        /** @var array<string, list<string>> $cache */
        static $cache = [];

        if (isset($cache[$character])) {
            return $cache[$character];
        }

        $variants = [$character];

        for ($index = 0; isset($variants[$index]); $index++) {
            $variant = $variants[$index];

            foreach ([
                mb_strtolower($variant),
                mb_strtoupper($variant),
                mb_convert_case($variant, MB_CASE_TITLE),
                mb_convert_case($variant, MB_CASE_FOLD),
            ] as $candidate) {
                if (mb_strlen($candidate) === 1 && ! in_array($candidate, $variants, true)) {
                    $variants[] = $candidate;
                }
            }
        }

        $folded = mb_convert_case($character, MB_CASE_FOLD);
        $equivalentCharacters = preg_split(
            '//u',
            self::REVERSE_EQUIVALENTS[$folded] ?? '',
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        foreach ($equivalentCharacters as $equivalent) {
            if (! in_array($equivalent, $variants, true)) {
                $variants[] = $equivalent;
            }
        }

        // Keep long-running workers bounded while still avoiding repeated Unicode
        // transformations for ordinary search terms and multiple searchable fields.
        if (count($cache) >= self::MAX_CACHED_CHARACTERS) {
            $cache = [];
        }

        return $cache[$character] = $variants;
    }
}
