<?php

namespace Lacodix\LaravelModelFilter\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterMode;
use Lacodix\LaravelModelFilter\Enums\TimeframeFilterPrecision;
use ValueError;

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

    public function apply(Builder $query): Builder
    {
        $this->relation = $query->getModel()->{$this->field}();

        return parent::apply($query);
    }

    public function getTimeframeModeLabel(TimeframeFilterMode $mode): string
    {
        return match ($mode) {
            TimeframeFilterMode::CURRENT => trans('model-filter::filters.current'),
            TimeframeFilterMode::EVER => trans('model-filter::filters.ever'),
            TimeframeFilterMode::TIMEFRAME => trans('model-filter::filters.in_timeframe'),
            TimeframeFilterMode::START_IN_TIMEFRAME => trans('model-filter::filters.start_in_timeframe'),
            TimeframeFilterMode::END_IN_TIMEFRAME => trans('model-filter::filters.end_in_timeframe'),
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
        return [
            ...$this->mode === FilterMode::CONTAINS ? $this->multiRules() : $this->singleRules(),

            $this->field . '.from' => 'nullable|' . $this->getDateRule(),
            $this->field . '.to' => 'nullable|' . $this->getDateRule(),
        ];
    }

    public function timeframeFilterMode(): TimeframeFilterMode
    {
        try {
            return TimeframeFilterMode::from($this->values[$this->field]['mode'] ?? 'ever');
        } catch (ValueError) {
            return TimeframeFilterMode::EVER;
        }
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
            $this->field . '.values' => 'in:' . implode(',', $this->options()),
        ];
    }

    protected function multiRules(): array
    {
        return [
            $this->field . '.values' => 'array',
            $this->field . '.values.*' => 'in:' . implode(',', $this->options()),
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
        return $this->values[$this->field]['values'] ?? null;
    }

    protected function getTimeframeFromOrigin(): Carbon
    {
        return $this->timeframeFilterMode()->needsDateValues()
            ? $this->parseInputDate($this->values[$this->field]['from'] ?? null)
            : now();
    }

    protected function getTimeframeToOrigin(): Carbon
    {
        return $this->timeframeFilterMode()->needsDateValues()
            ? $this->parseInputDate($this->values[$this->field]['to'] ?? null)
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
