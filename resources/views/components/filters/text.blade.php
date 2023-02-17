@php
    $name = $filter->queryName();
    $classes = 'text ' . $name;
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    <input
        class="filter-input"
        name="{{ $name }}"
        type="text"
        onchange="this.form.submit()"
        value="{{ request()->get($name, '') }}"
    >
</x-lacodix-filter::filters.layout>