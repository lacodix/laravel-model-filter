<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Support;

use Illuminate\Support\Str;

final class SearchInput
{
    public static function isAllowed(string $search): bool
    {
        $maxCharacters = self::configuredLimit('search_max_characters');
        if ($maxCharacters !== null && mb_strlen($search) > $maxCharacters) {
            return false;
        }

        $maxTerms = self::configuredLimit('search_max_terms');

        return $maxTerms === null || count(self::terms($search)) <= $maxTerms;
    }

    /** @return list<string> */
    public static function terms(string $search): array
    {
        $search = Str::squish($search);

        return $search === '' ? [] : explode(' ', $search);
    }

    private static function configuredLimit(string $key): ?int
    {
        $limit = config('model-filter.'.$key);

        return is_int($limit) && $limit > 0 ? $limit : null;
    }
}
