@php
    $name = $filter->getQueryName();
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    class="select"
>
    <select class="filter-input" name="{{ $name }}" onchange="this.form.submit()">
        <option value="">&mdash;</option>
        @foreach ($filter->options() as $option)
            <option value="{{ $option }}"{{ request()->get($name, '') === $option ? ' selected' : '' }}>
                {{ is_numeric($key) ? $option : $key }}
            </option>
        @endforeach
    </select>
</x-lacodix-filter::filters.layout>