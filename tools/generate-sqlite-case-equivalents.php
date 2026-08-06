<?php

declare(strict_types=1);

/**
 * Regenerate SqliteCaseFolding::REVERSE_EQUIVALENTS for the Unicode data bundled
 * with the current PHP intl/mbstring runtime. It implements the equivalence
 * semantics from Unicode CaseFolding.txt:
 * https://www.unicode.org/Public/UCD/latest/ucd/CaseFolding.txt
 *
 * Usage: php tools/generate-sqlite-case-equivalents.php
 */

/** @var array<string, list<string>> $foldGroups */
$foldGroups = [];

fwrite(
    STDERR,
    'Generating from Unicode '.implode('.', IntlChar::getUnicodeVersion()).PHP_EOL
);

for ($codePoint = 0; $codePoint <= IntlChar::CODEPOINT_MAX; $codePoint++) {
    if ($codePoint >= 0xD800 && $codePoint <= 0xDFFF) {
        continue;
    }

    $character = IntlChar::chr($codePoint);
    if ($character === null) {
        continue;
    }

    $folded = mb_convert_case($character, MB_CASE_FOLD);
    $foldGroups[$folded][] = $character;
}

/** @var array<string, string> $reverseEquivalents */
$reverseEquivalents = [];

foreach ($foldGroups as $folded => $characters) {
    if (count($characters) < 2) {
        continue;
    }

    foreach ($characters as $character) {
        if (array_diff($characters, directlyReachableCaseVariants($character)) !== []) {
            $reverseEquivalents[$folded] = implode('', $characters);

            continue 2;
        }
    }
}

ksort($reverseEquivalents);

foreach ($reverseEquivalents as $folded => $characters) {
    echo '        '.var_export($folded, true).' => '.var_export($characters, true).",\n";
}

/** @return list<string> */
function directlyReachableCaseVariants(string $character): array
{
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

    return $variants;
}
