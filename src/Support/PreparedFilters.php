<?php

declare(strict_types=1);

namespace Lacodix\LaravelModelFilter\Support;

use ArrayIterator;
use Countable;
use Illuminate\Database\Eloquent\Model;
use IteratorAggregate;
use Lacodix\LaravelModelFilter\Filters\Filter;
use Traversable;

/**
 * @template TModel of Model
 *
 * @implements IteratorAggregate<int, Filter<TModel>>
 */
final readonly class PreparedFilters implements Countable, IteratorAggregate
{
    /** @var list<Filter<TModel>> */
    private array $filters;

    /**
     * @param Filter<TModel> ...$filters
     */
    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }

    public function isEmpty(): bool
    {
        return $this->filters === [];
    }

    public function count(): int
    {
        return count($this->filters);
    }

    /**
     * @return list<string>
     */
    public function queryNames(): array
    {
        return array_map(
            static fn (Filter $filter): string => $filter->queryName(),
            $this->filters
        );
    }

    /**
     * @return list<Filter<TModel>>
     */
    public function all(): array
    {
        return $this->filters;
    }

    /**
     * @return Traversable<int, Filter<TModel>>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->filters);
    }
}
