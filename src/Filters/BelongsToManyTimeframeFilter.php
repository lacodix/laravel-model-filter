<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterMode;
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterPrecision;
use ValueError;

/**
 * @template TModel of Model
 *
 * @extends BelongsToManyFilter<TModel>
 */
class BelongsToManyTimeframeFilter extends BelongsToManyFilter
{
    protected string $component = 'timeframe';

    protected string $startField;
    protected string $endField;

    protected BelongsToMany $relation;

    protected TimeframeFilterPrecision $precision = TimeframeFilterPrecision::MONTH;

    public function setPrecision(TimeframeFilterPrecision $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    public function getPrecision(): TimeframeFilterPrecision
    {
        return $this->precision;
    }

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    public function applyFilter(Builder $query): Builder
    {
        $this->relation = $query->getModel()->{$this->field}();

        return parent::applyFilter($query);
    }

    public function getTimeframeModeLabel(TimeframeFilterMode $mode): string
    {
        return match ($mode) {
            TimeframeFilterMode::CURRENT => trans('model-filter::filters.current'),
            TimeframeFilterMode::EVER => trans('model-filter::filters.ever'),
            TimeframeFilterMode::TIMEFRAME => trans('model-filter::filters.in_timeframe'),
            TimeframeFilterMode::START_IN_TIMEFRAME => trans('model-filter::filters.start_in_timeframe'),
            TimeframeFilterMode::END_IN_TIMEFRAME => trans('model-filter::filters.end_in_timeframe'),
            TimeframeFilterMode::NEVER => trans('model-filter::filters.never'),
            TimeframeFilterMode::NOT_CURRENT => trans('model-filter::filters.not_current'),
        };
    }

    public function getDateInputType(): string
    {
        return match ($this->precision) {
            TimeframeFilterPrecision::DAY => 'date',
            TimeframeFilterPrecision::MONTH => 'month',
            TimeframeFilterPrecision::YEAR => 'number',
        };
    }

    public function rules(): array
    {
        if ($this->timeframeFilterMode()->isInverted()) {
            return [
                $this->queryName() . '.values' => 'nullable',
                $this->queryName() . '.from' => 'nullable|' . $this->getDateRule(),
                $this->queryName() . '.to' => 'nullable|' . $this->getDateRule(),
            ];
        }

        return [
            ...$this->mode === FilterMode::EQUAL ? $this->singleRules() : $this->multiRules(),

            $this->queryName() . '.from' => 'nullable|' . $this->getDateRule(),
            $this->queryName() . '.to' => 'nullable|' . $this->getDateRule(),
        ];
    }

    public function timeframeFilterMode(): TimeframeFilterMode
    {
        try {
            return TimeframeFilterMode::from($this->getValue()['mode'] ?? 'ever');
        } catch (ValueError) {
            return TimeframeFilterMode::EVER;
        }
    }

    protected function isInverted(): bool
    {
        return $this->timeframeFilterMode()->isInverted() || parent::isInverted();
    }

    /**
     * @param  Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    protected function applyInvertedFilter(Builder $query): Builder
    {
        if ($this->timeframeFilterMode() === TimeframeFilterMode::NEVER && empty($this->filterValues())) {
            return $query->whereDoesntHave($this->field);
        }

        if ($this->timeframeFilterMode() === TimeframeFilterMode::NOT_CURRENT && empty($this->filterValues())) {
            return $query->whereDoesntHave(
                $this->field,
                fn (Builder $query) => $this->getNotCurrentTimeframeQuery($query)
            );
        }

        return parent::applyInvertedFilter($query);
    }

    protected function getFilterQuery(Builder $query): Builder
    {
        return parent::getFilterQuery($query)
            ->when(
                in_array($this->timeframeFilterMode(), [TimeframeFilterMode::CURRENT, TimeframeFilterMode::TIMEFRAME]),
                fn ($query) => $this->getTimeframeFilterQuery($query)
            )
            ->when(
                $this->timeframeFilterMode() === TimeframeFilterMode::START_IN_TIMEFRAME,
                fn ($query) => $this->getTimeframeStartFilterQuery($query)
            )
            ->when(
                $this->timeframeFilterMode() === TimeframeFilterMode::END_IN_TIMEFRAME,
                fn ($query) => $this->getTimeframeEndFilterQuery($query)
            )
            ->when(
                $this->timeframeFilterMode() === TimeframeFilterMode::NOT_CURRENT,
                fn ($query) => $this->getNotCurrentTimeframeQuery($query)
            );
    }

    protected function getTimeframeFilterQuery(Builder $query): Builder
    {
        return $query
            ->where(
                fn ($query) => $query
                    ->whereNull($this->qualifyPivotColumn($this->startField))
                    ->orWhere(
                        $this->qualifyPivotColumn($this->startField),
                        '<=',
                        $this->getTimeframeEnd()
                    )
            )
            ->where(
                fn ($query) => $query
                    ->whereNull($this->qualifyPivotColumn($this->endField))
                    ->orWhere(
                        $this->qualifyPivotColumn($this->endField),
                        '>=',
                        $this->getTimeframeStart()
                    )
            );
    }

    protected function getNotCurrentTimeframeQuery(Builder $query): Builder
    {
        return $query
            ->where(
                fn ($query) => $query
                    ->whereNull($this->qualifyPivotColumn($this->startField))
                    ->orWhere($this->qualifyPivotColumn($this->startField), '<=', now()->format('Y-m-d H:i:s'))
            )
            ->where(
                fn ($query) => $query
                    ->whereNull($this->qualifyPivotColumn($this->endField))
                    ->orWhere($this->qualifyPivotColumn($this->endField), '>=', now()->format('Y-m-d H:i:s'))
            );
    }

    protected function getTimeframeStartFilterQuery(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyPivotColumn($this->startField), '>=', $this->getTimeframeStart())
            ->where($this->qualifyPivotColumn($this->startField), '<=', $this->getTimeframeEnd());
    }

    protected function getTimeframeEndFilterQuery(Builder $query): Builder
    {
        return $query
            ->where($this->qualifyPivotColumn($this->endField), '>=', $this->getTimeframeStart())
            ->where($this->qualifyPivotColumn($this->endField), '<=', $this->getTimeframeEnd());
    }

    protected function singleRules(): array
    {
        return [
            $this->queryName() . '.values' => 'in:' . implode(',', $this->options()),
        ];
    }

    protected function multiRules(): array
    {
        return [
            $this->queryName() . '.values' => 'array',
            $this->queryName() . '.values.*' => 'in:' . implode(',', $this->options()),
        ];
    }

    protected function getDateRule(): string
    {
        return match ($this->precision) {
            TimeframeFilterPrecision::DAY => 'date_format:' . config('model-filter.date_format'),
            TimeframeFilterPrecision::MONTH => 'date_format:' . config('model-filter.month_format'),
            TimeframeFilterPrecision::YEAR => 'numeric|min:1900',
        };
    }

    protected function qualifyPivotColumn(string $column): string
    {
        return $this->relation->qualifyPivotColumn($column);
    }

    protected function filterValues(): mixed
    {
        return $this->getValue()['values'] ?? null;
    }

    protected function getTimeframeFromOrigin(): Carbon
    {
        return $this->timeframeFilterMode()->needsDateValues()
            ? $this->parseInputDate($this->getValue()['from'] ?? null)
            : now();
    }

    protected function getTimeframeToOrigin(): Carbon
    {
        return $this->timeframeFilterMode()->needsDateValues()
            ? $this->parseInputDate($this->getValue()['to'] ?? null)
            : now();
    }

    protected function getTimeframeStart(): string
    {
        return match ($this->precision) {
            TimeframeFilterPrecision::DAY => $this->getTimeframeFromOrigin()->startOfDay()->format('Y-m-d H:i:s'),
            TimeframeFilterPrecision::MONTH => $this->getTimeframeFromOrigin()->startOfMonth()->format('Y-m-d H:i:s'),
            TimeframeFilterPrecision::YEAR => $this->getTimeframeFromOrigin()->startOfYear()->format('Y-m-d H:i:s'),
        };
    }

    protected function getTimeframeEnd(): string
    {
        return match ($this->precision) {
            TimeframeFilterPrecision::DAY => $this->getTimeframeToOrigin()->endOfDay()->format('Y-m-d H:i:s'),
            TimeframeFilterPrecision::MONTH => $this->getTimeframeToOrigin()->endOfMonth()->format('Y-m-d H:i:s'),
            TimeframeFilterPrecision::YEAR => $this->getTimeframeToOrigin()->endOfYear()->format('Y-m-d H:i:s'),
        };
    }

    protected function parseInputDate(?string $date): Carbon
    {
        return match ($this->precision) {
            TimeframeFilterPrecision::DAY, TimeframeFilterPrecision::MONTH => Carbon::parse($date),
            TimeframeFilterPrecision::YEAR => Carbon::create($date),
        };
    }
}
