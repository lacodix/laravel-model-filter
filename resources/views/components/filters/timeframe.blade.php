@php
    $multiple = $filter->mode->needsMultipleValues();
    $name = $filter->queryName();
    $classes = 'select ' . $name;
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    <select
        class="filter-input"
        name="{{ $name }}[values]"
        onchange="this.form.submit()"
        @if ($multiple)
            multiple="multiple"
        @endif
    >
        @if (! $multiple)
            <option value="">&mdash;</option>
        @endif
        @foreach ($filter->options() as $key => $option)
            <option value="{{ $option }}"{{ request()->get($name, [])['values'] ?? '' === $option ? ' selected' : '' }}>
                {{ is_numeric($key) ? $option : $key }}
            </option>
        @endforeach
    </select>

    <div class="filter-timeframe-modes">
        @foreach(\Lacodix\LaravelModelFilter\Enums\TimeframeFilterMode::cases() as $mode)
            <label class="filter-input">
                <input
                    class="timeframe-mode"
                    name="{{ $name }}_mode"
                    type="radio"
                    value="{{ $mode->value }}"
                    onchange="this.form.submit()"
                    {{ (request()->get($name, [])['mode'] ?? '') === $mode->value ?? '' ? 'checked' : '' }}
                >
                {{ $filter->getTimeframeModeLabel($mode) }}
            </label>
        @endforeach
    </div>

    <div class="filter-timeframe-dates">
        <input
            class="filter-input"
            name="{{ $name }}[from]"
            type="{{ $filter->getDateInputType() }}"
            id="{{ $name }}_from"
            value="{{ request()->get($name, [])['from'] ?? '' }}"
            onchange="this.form.submit()"
        >
        <input
            class="filter-input"
            name="{{ $name }}[to]"
            type="{{ $filter->getDateInputType() }}"
            id="{{ $name }}_to"
            value="{{ request()->get($name, [])['to'] ?? '' }}"
            onchange="this.form.submit()"
        >
    </div>

</x-lacodix-filter::filters.layout>
