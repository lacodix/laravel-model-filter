@props([
    'filter',
])

<div
    {{ $attributes->class('filter-container') }}
>
    <div class="filter-title">
        {{ $filter->title() }}
    </div>

    <div class="filter-content">
        {{ $slot }}
    </div>
</div>