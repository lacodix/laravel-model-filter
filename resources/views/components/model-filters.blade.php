@props([
    'model',
    'group' => '__default'
])

@php
    if (is_string($model)) {
        $model = new $model();
    }
@endphp

<form method="get">
    @foreach ($model->filters($group) as $filter)
        <x-dynamic-component
            :component="$filter->getComponent()"
            :filter="$filter"
        />
    @endforeach
</form>