@php
    $multiple = $filter->mode->needsMultipleValues();
    $varName = $filter->queryName();
    $name = $varName . ($multiple ? '[]' : '');
    $classes = 'date ' . $varName;
@endphp
<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    @if ($multiple)
        <input
            class="filter-input"
            name="{{ $name }}"
            type="date"
            id="{{ $name }}_from"
            onchange="if (this.value && document.getElementById('{{ $name }}_to').value || ! this.value && ! document.getElementById('{{ $name }}_to').value) this.form.submit();"
            value="{{ request()->input($varName, [])[0] ?? '' }}"
        >
        <input
            class="filter-input"
            name="{{ $name }}"
            type="date"
            id="{{ $name }}_to"
            onchange="if (this.value && document.getElementById('{{ $name }}_from').value || ! this.value && ! document.getElementById('{{ $name }}_from').value) this.form.submit();"
            value="{{ request()->input($varName, [])[1] ?? '' }}"
        >
    @else
        <input
            class="filter-input"
            name="{{ $name }}"
            type="date"
            onchange="this.form.submit()"
            value="{{ request()->input($varName, '') }}"
        >
    @endif
</x-lacodix-filter::filters.layout>