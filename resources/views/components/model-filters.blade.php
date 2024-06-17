@props([
    'model',
    'method' => 'get',
    'group' => '__default',
])

@php
    if (is_string($model)) {
        $model = new $model();
    }
@endphp

<form {{ $attributes->merge(['method' => $method]) }}>
    @foreach ($model->filterInstances($group) as $filter)
        <x-dynamic-component
            :component="$filter->component()"
            :filter="$filter"
        />
    @endforeach

    {{ $footer ?? '' }}
</form>
