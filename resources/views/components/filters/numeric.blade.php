@php
    use Illuminate\Support\Arr;
    use Lacodix\LaravelModelFilter\Support\DetachedForm;

    $multiple = $filter->mode->needsMultipleValues();
    $varName = $filter->queryName();
    $name = $varName . ($multiple ? '[]' : '');
    $classes = 'numeric ' . $varName;
    $hasPresets = $filter->hasPresets();
    $presetState = $hasPresets ? $filter->presetState(request()->input($varName)) : null;
    // Ids of the inputs a preset writes its values into, in the order of the preset values.
    $valueIds = $multiple ? [$varName . '_from', $varName . '_to'] : [$varName . '_value'];
    // A preset has no identity that belongs into the query string, and its radio name could
    // collide with the query name of another filter. Pointing the form attribute at an id no
    // form has leaves the radios without a form owner: no form ever submits them, whatever
    // triggers the submit - while they keep grouping by name. That is also why they submit
    // via closest('form') instead of this.form, which is null for them.
    //
    // Grouping is document wide because of that, so rendering the same filter set twice on
    // one page merges the two groups. That case is unsupported anyway: the value ids below
    // would exist twice as well, see docs/advanced-usage/filter-presets.md.
    $detachedForm = DetachedForm::id();
@endphp
<x-lacodix-filter::filters.layout
    :filter="$filter"
    :class="$classes"
>
    @if ($hasPresets)
        <div class="filter-presets">
            <label class="filter-input">
                <input
                    class="filter-preset"
                    name="{{ $varName }}_preset"
                    form="{{ $detachedForm }}"
                    type="radio"
                    value=""
                    @checked($presetState === 'none')
                    onchange="@js($valueIds).forEach(id => document.getElementById(id).value = ''); this.closest('form').submit();"
                >
                {{ trans('model-filter::filters.all') }}
            </label>

            @foreach ($filter->presets() as $index => $preset)
                <label class="filter-input">
                    <input
                        class="filter-preset"
                        name="{{ $varName }}_preset"
                        form="{{ $detachedForm }}"
                        type="radio"
                        value="{{ $index }}"
                        @checked($presetState === $index)
                        onchange="const values = @js(array_values(Arr::wrap($preset['values']))); @js($valueIds).forEach((id, position) => document.getElementById(id).value = values[position] ?? ''); this.closest('form').submit();"
                    >
                    {{ $preset['label'] }}
                </label>
            @endforeach

            <label class="filter-input">
                <input
                    class="filter-preset"
                    name="{{ $varName }}_preset"
                    form="{{ $detachedForm }}"
                    type="radio"
                    value="custom"
                    @checked($presetState === 'custom')
                    onchange="const custom = document.getElementById('{{ $varName }}_custom'); custom.style.display = ''; custom.querySelector('input').focus();"
                >
                {{ trans('model-filter::filters.custom') }}
            </label>
        </div>
    @endif

    <div
        class="filter-custom-values"
        id="{{ $varName }}_custom"
        @if ($hasPresets && $presetState !== 'custom') style="display: none;" @endif
    >
        @if ($multiple)
            <input
                class="filter-input"
                name="{{ $name }}"
                type="number"
                id="{{ $varName }}_from"
                onchange="if (this.value && document.getElementById('{{ $varName }}_to').value || ! this.value && ! document.getElementById('{{ $varName }}_to').value) this.form.submit();"
                value="{{ request()->input($varName, [])[0] ?? '' }}"
            >
            <input
                class="filter-input"
                name="{{ $name }}"
                type="number"
                id="{{ $varName }}_to"
                onchange="if (this.value && document.getElementById('{{ $varName }}_from').value || ! this.value && ! document.getElementById('{{ $varName }}_from').value) this.form.submit();"
                value="{{ request()->input($varName, [])[1] ?? '' }}"
            >
        @else
            <input
                class="filter-input"
                name="{{ $name }}"
                type="number"
                id="{{ $varName }}_value"
                onchange="this.form.submit()"
                value="{{ request()->input($varName, '') }}"
            >
        @endif
    </div>
</x-lacodix-filter::filters.layout>
