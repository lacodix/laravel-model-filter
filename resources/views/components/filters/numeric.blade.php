@php
    $multiple = $filter->mode->needsMultipleValues();
    $varName = $filter->queryName();
    $name = $varName . ($multiple ? '[]' : '');
    $classes = 'numeric ' . $varName;
@endphp
<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    @if ($multiple)
        <input
            class="filter-input"
            name="{{ $name }}"
            type="number"
            id="{{ $name }}_from"
            onchange="if (this.value && document.getElementById('{{ $name }}_to').value || ! this.value && ! document.getElementById('{{ $name }}_to').value) this.form.submit();"
            value="{{ request()->get($varName, [])[0] ?? '' }}"
        >
        <input
            class="filter-input"
            name="{{ $name }}"
            type="number"
            id="{{ $name }}_to"
            onchange="if (this.value && document.getElementById('{{ $name }}_from').value || ! this.value && ! document.getElementById('{{ $name }}_from').value) this.form.submit();"
            value="{{ request()->get($varName, [])[1] ?? '' }}"
        >
    @else
        <input
            class="filter-input"
            name="{{ $name }}"
            type="number"
            onchange="this.form.submit()"
            value="{{ request()->get($varName, '') }}"
        >
    @endif
</x-lacodix-filter::filters.layout>