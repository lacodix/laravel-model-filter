@php
    $name = $filter->queryName();
    $classes = 'select ' . $name;
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    <select class="filter-input" name="{{ $name }}" onchange="this.form.submit()">
        <option value="">&mdash;</option>
        @foreach ($filter->options() as $key => $option)
            <option value="{{ $option }}"{{ request()->get($name, '') === $option ? ' selected' : '' }}>
                {{ is_numeric($key) ? $option : $key }}
            </option>
        @endforeach
    </select>
</x-lacodix-filter::filters.layout>