<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Filters\NumericFilter;
use Tests\Models\Post;

function presetFilter(): NumericFilter
{
    return (new NumericFilter('counter'))
        ->setQueryName('counter_presets')
        ->setMode(FilterMode::BETWEEN)
        ->setPresets([
            ['label' => 'Low', 'values' => ['', 5000]],
            ['label' => 'Mid', 'values' => [5001, 10000]],
            ['label' => 'High', 'values' => [10001, '']],
        ]);
}

it('has no presets by default', function () {
    $filter = (new NumericFilter('counter'))->setQueryName('counter_between');

    expect($filter->presets())->toEqual([])
        ->and($filter->hasPresets())->toBeFalse();
});

it('knows about its presets', function () {
    expect(presetFilter()->hasPresets())->toBeTrue()
        ->and(presetFilter()->presets())->toHaveCount(3)
        ->and(presetFilter()->presets()[1]['label'])->toEqual('Mid');
});

it('throws for presets without label or values', function (array $presets) {
    presetFilter()->setPresets($presets);
})->throws(InvalidArgumentException::class)->with([
    'no label' => [[['values' => ['', 5000]]]],
    'no values' => [[['label' => 'Low']]],
    'no array' => [['Low']],
]);

it('detects the active preset for matching values', function () {
    expect(presetFilter()->activePreset(['', 5000]))->toEqual(0)
        ->and(presetFilter()->activePreset([5001, 10000]))->toEqual(1)
        ->and(presetFilter()->activePreset([10001, '']))->toEqual(2);
});

it('detects the active preset independent of the value type', function () {
    // A request delivers strings, a preset is written with integers.
    expect(presetFilter()->activePreset(['', '5000']))->toEqual(0)
        ->and(presetFilter()->activePreset(['5001', '10000']))->toEqual(1)
        ->and(presetFilter()->activePreset([null, 5000]))->toEqual(0);
});

it('has no active preset for custom values', function () {
    expect(presetFilter()->activePreset([42, 84]))->toBeNull()
        ->and(presetFilter()->activePreset([5001, '']))->toBeNull();
});

it('reports the preset state for empty, preset and custom values', function () {
    expect(presetFilter()->presetState(['', '']))->toEqual('none')
        ->and(presetFilter()->presetState(null))->toEqual('none')
        ->and(presetFilter()->presetState([5001, 10000]))->toEqual(1)
        ->and(presetFilter()->presetState([42, 84]))->toEqual('custom');
});

it('reindexes presets so that the index identifies an entry', function () {
    $filter = presetFilter()->setPresets([
        3 => ['label' => 'Low', 'values' => ['', 5000]],
        7 => ['label' => 'Mid', 'values' => [5001, 10000]],
    ]);

    expect(array_keys($filter->presets()))->toBe([0, 1])
        ->and($filter->activePreset([5001, 10000]))->toBe(1);
});

it('validates presets that are built dynamically, not only fluently set ones', function () {
    $filter = new class extends NumericFilter {
        protected string $field = 'counter';

        protected function definePresets(): array
        {
            return [['label' => 'Broken']];
        }
    };

    // Without the values key this would end up as an "Undefined array key" inside a view.
    $filter->presets();
})->throws(InvalidArgumentException::class);

it('takes dynamically built presets like fluently set ones', function () {
    $filter = new class extends NumericFilter {
        public FilterMode $mode = FilterMode::BETWEEN;

        protected string $field = 'counter';

        protected function definePresets(): array
        {
            return [['label' => 'Dynamic', 'values' => [5001, 10000]]];
        }
    };

    expect($filter->hasPresets())->toBeTrue()
        ->and($filter->presets()[0]['label'])->toBe('Dynamic')
        ->and($filter->presetState([5001, 10000]))->toBe(0);
});

it('handles presets of single value filters', function () {
    $filter = (new NumericFilter('counter'))
        ->setQueryName('counter_preset_single')
        ->setMode(FilterMode::LOWER_OR_EQUAL)
        ->setPresets([
            ['label' => 'Single', 'values' => 5000],
        ]);

    expect($filter->presetState(5000))->toEqual(0)
        ->and($filter->presetState('5000'))->toEqual(0)
        ->and($filter->presetState(42))->toEqual('custom')
        ->and($filter->presetState(''))->toEqual('none');
});

it('filters by preset values like by manual ones', function () {
    Post::factory()->count(3)->create(['counter' => 1000]);
    Post::factory()->count(2)->create(['counter' => 7000]);

    // The values of preset 'Mid', a preset is nothing but a shortcut to these values.
    expect(Post::filter(['counter_presets' => [5001, 10000]])->count())->toEqual(2)
        ->and(Post::filter(['counter_presets' => ['', 5000]])->count())->toEqual(3);
});
