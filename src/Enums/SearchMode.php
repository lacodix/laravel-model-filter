<?php

namespace Lacodix\LaravelModelFilter\Enums;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Lacodix\LaravelModelFilter\Support\SearchInput;
use Lacodix\LaravelModelFilter\Support\SqliteCaseFolding;

enum SearchMode
{
    case EQUAL;
    case LIKE;
    case LIKE_CASE_SENSITIVE;
    case STARTS_WITH;
    case STARTS_WITH_CASE_SENSITIVE;
    case ENDS_WITH;
    case ENDS_WITH_CASE_SENSITIVE;
    case CONTAINS_ANY;
    case CONTAINS_ANY_CASE_SENSITIVE;
    case CONTAINS_ALL;
    case CONTAINS_ALL_CASE_SENSITIVE;

    public static function fromString(string $value): self
    {
        return match (strtolower($value)) {
            'equal' => self::EQUAL,
            'starts_with' => self::STARTS_WITH,
            'starts_with_case' => self::STARTS_WITH_CASE_SENSITIVE,
            'ends_with' => self::ENDS_WITH,
            'ends_with_case' => self::ENDS_WITH_CASE_SENSITIVE,
            'contains_any' => self::CONTAINS_ANY,
            'contains_any_case' => self::CONTAINS_ANY_CASE_SENSITIVE,
            'contains_all' => self::CONTAINS_ALL,
            'contains_all_case' => self::CONTAINS_ALL_CASE_SENSITIVE,
            'like_case' => self::LIKE_CASE_SENSITIVE,
            default => self::LIKE,
        };
    }

    public function applyQuery(Builder $query, string $field, string $search): Builder
    {
        return $this->apply($query, $field, $search, false);
    }

    /**
     * Apply the search while treating database wildcard characters as ordinary input.
     */
    public function applyLiteralQuery(Builder $query, string $field, string $search): Builder
    {
        return $this->apply($query, $field, $search, true);
    }

    private function apply(Builder $query, string $field, string $search, bool $literal): Builder
    {
        $terms = SearchInput::terms($search);
        $mode = $this->normalizedFor($terms);

        if ($mode === self::EQUAL) {
            return $query->orWhere($field, $search);
        }

        if (in_array($mode, [self::CONTAINS_ANY, self::CONTAINS_ANY_CASE_SENSITIVE], true)) {
            foreach ($terms as $part) {
                self::addLike($query, $field, $part, 'contains', $mode->isCaseSensitive(), $literal, 'or');
            }

            return $query;
        }

        if (in_array($mode, [self::CONTAINS_ALL, self::CONTAINS_ALL_CASE_SENSITIVE], true)) {
            $query->orWhere(static function (Builder $innerQuery) use ($field, $terms, $mode, $literal): void {
                foreach ($terms as $part) {
                    self::addLike($innerQuery, $field, $part, 'contains', $mode->isCaseSensitive(), $literal, 'and');
                }
            });

            return $query;
        }

        self::addLike(
            $query,
            $field,
            $search,
            $mode->position(),
            $mode->isCaseSensitive(),
            $literal,
            'or'
        );

        return $query;
    }

    /** @param list<string> $terms */
    private function normalizedFor(array $terms): self
    {
        if (count($terms) > 1) {
            return $this;
        }

        return match ($this) {
            self::CONTAINS_ANY,
            self::CONTAINS_ALL => self::LIKE,
            self::CONTAINS_ANY_CASE_SENSITIVE,
            self::CONTAINS_ALL_CASE_SENSITIVE => self::LIKE_CASE_SENSITIVE,
            default => $this,
        };
    }

    /**
     * @param  'contains'|'starts_with'|'ends_with'  $position
     * @param  'and'|'or'  $boolean
     */
    private static function addLike(
        Builder $query,
        string $field,
        string $search,
        string $position,
        bool $caseSensitive,
        bool $literal,
        string $boolean
    ): void {
        $grammar = $query->getGrammar();
        $wrappedField = $grammar->wrap($field);

        if ($grammar instanceof SQLiteGrammar) {
            $value = $caseSensitive
                ? ($literal ? SqliteCaseFolding::escapeGlob($search) : $search)
                : SqliteCaseFolding::globPattern($search, $literal);

            $query->whereRaw(
                $wrappedField.' GLOB ?',
                [self::pattern($value, $position, '*')],
                $boolean
            );

            return;
        }

        $operator = match (true) {
            $grammar instanceof PostgresGrammar && ! $caseSensitive => 'ILIKE',
            $grammar instanceof PostgresGrammar => 'LIKE',
            $caseSensitive => 'LIKE BINARY',
            default => 'LIKE',
        };
        $foldCase = ! $caseSensitive && ! $grammar instanceof PostgresGrammar;
        $column = $foldCase ? 'LOWER('.$wrappedField.')' : $wrappedField;
        $value = $caseSensitive ? $search : mb_strtolower($search);

        // Laravel's PostgreSQL grammar adds this cast for LIKE operators used via
        // where(). Raw predicates must preserve it explicitly for non-text fields.
        if ($grammar instanceof PostgresGrammar) {
            $column .= '::text';
        }

        if ($literal) {
            $value = self::escapeLike($value);
        }

        $escapeClause = $literal ? " ESCAPE '!'" : '';

        $query->whereRaw(
            $column.' '.$operator.' ?'.$escapeClause,
            [self::pattern($value, $position, '%')],
            $boolean
        );
    }

    private function position(): string
    {
        return match ($this) {
            self::STARTS_WITH,
            self::STARTS_WITH_CASE_SENSITIVE => 'starts_with',
            self::ENDS_WITH,
            self::ENDS_WITH_CASE_SENSITIVE => 'ends_with',
            default => 'contains',
        };
    }

    private function isCaseSensitive(): bool
    {
        return in_array($this, [
            self::LIKE_CASE_SENSITIVE,
            self::STARTS_WITH_CASE_SENSITIVE,
            self::ENDS_WITH_CASE_SENSITIVE,
            self::CONTAINS_ANY_CASE_SENSITIVE,
            self::CONTAINS_ALL_CASE_SENSITIVE,
        ], true);
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }

    /**
     * @param  'contains'|'starts_with'|'ends_with'  $position
     */
    private static function pattern(string $value, string $position, string $wildcard): string
    {
        return match ($position) {
            'starts_with' => $value.$wildcard,
            'ends_with' => $wildcard.$value,
            'contains' => $wildcard.$value.$wildcard,
        };
    }
}
