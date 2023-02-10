@php
    $name = $filter->queryName();
@endphp

<x-lacodix-filter::filters.layout
    :filter="$filter"
    class="select"
>
    <input
        class="filter-input"
        name="{{ $name }}"
        type="text"
        onchange="this.form.submit()"
        value="{{ request()->get($name, '') }}"
    >
</x-lacodix-filter::filters.layout>