<?php

declare(strict_types=1);

namespace Tests\Support;

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\BelongsToFilter;
use Lacodix\LaravelModelFilter\Filters\BelongsToManyFilter;
use Lacodix\LaravelModelFilter\Filters\BelongsToManyTimeframeFilter;
use Lacodix\LaravelModelFilter\Filters\BooleanFilter;
use Lacodix\LaravelModelFilter\Filters\DateFilter;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Filters\Filter;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
use Lacodix\LaravelModelFilter\Filters\OptionFilter;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Lacodix\LaravelModelFilter\Filters\TrashedFilter;
use Tests\Filters\TagFilter;
use Tests\Filters\TagTimeframeFilter;
use Tests\Models\Tag;

enum InputContractStatus: string
{
    case PAGE = 'page';
    case POST = 'post';
}

final class InputContractFilterFactory
{
    public static function make(string $family): Filter
    {
        return match ($family) {
            'string' => new StringFilter('title'),
            'boolean' => new BooleanFilter('published'),
            'numeric' => new NumericFilter('counter'),
            'numeric_range' => (new NumericFilter('counter'))->setMode(FilterMode::BETWEEN),
            'date' => new DateFilter('created_at'),
            'date_range' => (new DateFilter('created_at'))->setMode(FilterMode::BETWEEN),
            'select' => self::select(),
            'select_multi' => self::select()->setMode(FilterMode::CONTAINS),
            'enum' => (new EnumFilter('type'))->setEnum(InputContractStatus::class),
            'enum_multi' => (new EnumFilter('type'))
                ->setEnum(InputContractStatus::class)
                ->setMode(FilterMode::CONTAINS),
            'option' => new OptionFilter(['published']),
            'trashed' => new TrashedFilter,
            'belongs_to' => self::belongsTo(),
            'belongs_to_multi' => self::belongsTo()->setMode(FilterMode::CONTAINS),
            'belongs_to_many' => self::belongsToMany(),
            'belongs_to_many_multi' => self::belongsToMany()->setMode(FilterMode::CONTAINS),
            'timeframe' => self::timeframe(),
            'timeframe_multi' => self::timeframe()->setMode(FilterMode::CONTAINS),
        };
    }

    private static function select(): SelectFilter
    {
        return (new SelectFilter('type'))->setOptions(['Page' => 'page', 'Post' => 'post']);
    }

    private static function belongsTo(): BelongsToFilter
    {
        return (new BelongsToFilter('type'))
            ->setRelationModel(Tag::class)
            ->setTitleColumn('title')
            ->setOptions(['Page' => 'page', 'Post' => 'post']);
    }

    private static function belongsToMany(): BelongsToManyFilter
    {
        return (new TagFilter)->setOptions(['Tag 1' => '1', 'Tag 2' => '2']);
    }

    private static function timeframe(): BelongsToManyTimeframeFilter
    {
        return (new TagTimeframeFilter)->setOptions(['Tag 1' => '1', 'Tag 2' => '2']);
    }
}
