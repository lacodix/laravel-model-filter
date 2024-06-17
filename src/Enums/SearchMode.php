<?php

namespace Lacodix\LaravelModelFilter\Enums;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        // if there is no space, it's all just a like
        if (! str_contains($search, ' ') && in_array($this, [
            self::CONTAINS_ANY,
            self::CONTAINS_ANY_CASE_SENSITIVE,
            self::CONTAINS_ALL,
            self::CONTAINS_ALL_CASE_SENSITIVE,
        ])) {
            return match ($this) {
                self::CONTAINS_ANY,
                self::CONTAINS_ALL => self::LIKE->applyQuery($query, $field, $search),
                self::CONTAINS_ANY_CASE_SENSITIVE,
                self::CONTAINS_ALL_CASE_SENSITIVE => self::LIKE_CASE_SENSITIVE->applyQuery($query, $field, $search),
            };
        }

        $grammar = $query->getGrammar();

        return match (true) {
            $grammar instanceof PostgresGrammar => $this->applyQueryPostgres($query, $field, $search),
            $grammar instanceof SQLiteGrammar => $this->applyQuerySQLite($query, $field, $search),
            default => $this->applyQueryMySql($query, $field, $search),
        };
    }

    // Postgres -> always Case Sensitive, ILike switches
    private function applyQueryPostgres(Builder $query, string $field, string $search): Builder
    {
        match ($this) {
            self::EQUAL => $query->orWhere($field, $search),
            self::STARTS_WITH => $query->orWhere($field, 'ILIKE', strtolower($search) . '%'),
            self::STARTS_WITH_CASE_SENSITIVE => $query->orWhere($field, 'LIKE', $search . '%'),
            self::ENDS_WITH => $query->orWhere($field, 'ILIKE', '%' . strtolower($search)),
            self::ENDS_WITH_CASE_SENSITIVE => $query->orWhere($field, 'LIKE', '%' . $search),
            self::LIKE_CASE_SENSITIVE => $query->orWhere($field, 'LIKE', '%' . $search . '%'),
            self::CONTAINS_ANY => collect(explode(' ', Str::squish($search)))
                ->each(static fn ($part) => $query->orWhere($field, 'ILIKE', '%' . strtolower($part) . '%')),
            self::CONTAINS_ANY_CASE_SENSITIVE => collect(explode(' ', Str::squish($search)))
                ->each(static fn ($part) => $query->orWhere($field, 'LIKE', '%' . $part . '%')),
            self::CONTAINS_ALL => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(static fn ($part) => $innerQuery->where($field, 'ILIKE', '%' . strtolower($part) . '%'));
            }),
            self::CONTAINS_ALL_CASE_SENSITIVE => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(static fn ($part) => $innerQuery->where($field, 'LIKE', '%' . $part . '%'));
            }),
            default => $query->orWhere($field, 'ILIKE', '%' . strtolower($search) . '%'),
        };

        return $query;
    }

    // SQLite -> Like never Case Sensitive, Glob is it
    private function applyQuerySQLite(Builder $query, string $field, string $search): Builder
    {
        match ($this) {
            self::EQUAL => $query->orWhere($field, $search),
            self::STARTS_WITH => $query->orWhere($field, 'LIKE', strtolower($search) . '%'),
            self::STARTS_WITH_CASE_SENSITIVE => $query->getQuery()->orWhereRaw($query->qualifyColumn($field) . ' GLOB "' . $search . '*"'),
            self::ENDS_WITH => $query->orWhere($field, 'LIKE', '%' . strtolower($search)),
            self::ENDS_WITH_CASE_SENSITIVE => $query->getQuery()->orWhereRaw($query->qualifyColumn($field) . ' GLOB "*' . $search . '"'),
            self::LIKE_CASE_SENSITIVE => $query->getQuery()->orWhereRaw($query->qualifyColumn($field) . ' GLOB "*' . $search . '*"'),
            self::CONTAINS_ANY => collect(explode(' ', Str::squish($search)))
                ->each(static fn ($part) => $query->orWhere($field, 'LIKE', '%' . strtolower($part) . '%')),
            self::CONTAINS_ANY_CASE_SENSITIVE => collect(explode(' ', Str::squish($search)))
                ->each(static fn ($part) => $query->getQuery()->orWhereRaw($query->qualifyColumn($field) . ' GLOB "*' . $part . '*"')),
            self::CONTAINS_ALL => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(static fn ($part) => $innerQuery->where($field, 'LIKE', '%' . strtolower($part) . '%'));
            }),
            self::CONTAINS_ALL_CASE_SENSITIVE => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(static fn ($part) => $innerQuery->getQuery()->whereRaw($innerQuery->qualifyColumn($field) . ' GLOB "*' . $part . '*"'));
            }),
            default => $query->orWhere($field, 'LIKE', '%' . strtolower($search) . '%'),
        };

        return $query;
    }

    private function applyQueryMySql(Builder $query, string $field, string $search): Builder
    {
        match ($this) {
            self::EQUAL => $query->orWhere($field, $search),
            self::STARTS_WITH => $query->orWhere(
                DB::raw('LOWER('.$query->qualifyColumn($field).')'),
                'LIKE',
                strtolower($search) . '%'
            ),
            self::STARTS_WITH_CASE_SENSITIVE => $query->orWhere($field, 'LIKE BINARY', $search . '%'),
            self::ENDS_WITH => $query->orWhere(
                DB::raw('LOWER('.$query->qualifyColumn($field).')'),
                'LIKE',
                '%' . strtolower($search)
            ),
            self::ENDS_WITH_CASE_SENSITIVE => $query->orWhere($field, 'LIKE BINARY', '%' . $search),
            self::LIKE_CASE_SENSITIVE => $query->orWhere($field, 'LIKE BINARY', '%' . $search . '%'),
            self::CONTAINS_ANY => collect(explode(' ', Str::squish($search)))
                ->each(
                    static fn ($part) => $query->orWhere(
                        DB::raw('LOWER('.$query->qualifyColumn($field).')'),
                        'LIKE',
                        '%' . strtolower($part) . '%'
                    )
                ),
            self::CONTAINS_ANY_CASE_SENSITIVE => collect(explode(' ', Str::squish($search)))
                ->each(static fn ($part) => $query->orWhere($field, 'LIKE BINARY', '%' . $part . '%')),
            self::CONTAINS_ALL => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(
                        static fn ($part) => $innerQuery->where(
                            DB::raw('LOWER('.$innerQuery->qualifyColumn($field).')'),
                            'LIKE',
                            '%' . strtolower($part) . '%'
                        )
                    );
            }),
            self::CONTAINS_ALL_CASE_SENSITIVE => $query->orWhere(static function (Builder $innerQuery) use ($search, $field): void {
                collect(explode(' ', Str::squish($search)))
                    ->each(static fn ($part) => $innerQuery->where($field, 'LIKE BINARY', '%' . $part . '%'));
            }),
            default => $query->orWhere(
                DB::raw('LOWER('.$query->qualifyColumn($field).')'),
                'LIKE',
                '%' . strtolower($search) . '%'
            ),
        };

        return $query;
    }
}
