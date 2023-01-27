@props([
    'model',
])

@php
    if (is_string($model)) {
        $model = new $model();
    }
@endphp

<form method="get">
    @foreach ($model->filters() as $filter)
        <x-dynamic-component
            :component="$filter->getComponent()"
            :filter="$filter"
        />
    @endforeach
</form>