<?php

use Lacodix\LaravelModelFilter\Support\DetachedForm;
use Tests\Models\Post;

beforeEach(function () {
    Post::factory()
        ->state([
            'type' => 'page',
        ])
        ->count(15)
        ->create();

    Post::factory()
        ->state([
            'type' => 'post',
        ])
        ->count(10)
        ->create();
});

it('can be filtered by select', function () {
    $view = $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_lower_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_greater_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between_exclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_not_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_not_between_inclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_today')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_greater_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'created_at_between_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'type_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'starts_with')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'ends_with')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'contains')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'equals')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'boolfilter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_lower_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_greater_filter')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between_exclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_not_between')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_not_between_inclusive')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_exact')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_greater_filter_throws')));
    $view->assertSee(ucwords(str_replace('_', ' ', 'counter_between_throws')));
});

it('renders presets of a numeric filter', function () {
    $view = $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    $view->assertSee('Preset Low Counter');
    $view->assertSee('Preset Mid Counter');
    $view->assertSee('Preset High Counter');
    $view->assertSee('Preset Single Counter');

    // The generic entries around the presets ...
    $view->assertSee(trans('model-filter::filters.all'));
    $view->assertSee(trans('model-filter::filters.custom'));

    // ... and the custom inputs, hidden as long as no custom values are filtered for.
    expect((string) $view)->toMatch('/id="counter_presets_custom"\s+style="display: none;"/');
});

it('writes preset values into the input of a single value filter', function () {
    $html = (string) $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    // The single input is the only target a preset of this filter writes into.
    expect($html)->toContain('id="counter_presets_single_value"')
        ->toContain('JSON.parse(\'[\\u0022counter_presets_single_value\\u0022]\').forEach(id => document.getElementById(id).value');
});

it('keeps the preset radios out of every form submit', function () {
    $html = (string) $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    $document = new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    libxml_clear_errors();

    $forms = iterator_to_array((new DOMXPath($document))->query('//form/@id') ?: []);
    $formIds = array_map(static fn (DOMNode $id) => $id->nodeValue, $forms);

    $radios = (new DOMXPath($document))->query('//input[contains(@class, "filter-preset")]');

    expect($radios->length)->toBeGreaterThan(0);

    // A preset has no identity that belongs into the query string. Every radio points its
    // form attribute at an id no form has, so it has no form owner and is submitted by no
    // form at all - no matter what triggers the submit (own handler, value input, another
    // filter, a native submit).
    foreach ($radios as $radio) {
        expect($radio)->toBeInstanceOf(DOMElement::class)
            ->and($radio->getAttribute('form'))->not->toBe('')
            // Derived, not hard coded: a fixed id could be taken by a form of the
            // surrounding application, which would adopt the radios after all.
            ->and($radio->getAttribute('form'))->toBe(DetachedForm::id())
            ->and($formIds)->not->toContain($radio->getAttribute('form'))
            // this.form is null without a form owner, so they must not rely on it.
            ->and($radio->getAttribute('onchange'))->not->toContain('this.form');
    }
});

it('does not render preset markup for a filter without presets', function () {
    $view = $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    $view->assertDontSee('name="counter_between_preset"', false);
    $view->assertSee('id="counter_between_custom"', false);
});

it('preselects the preset the current filter values belong to', function () {
    // Mid preset, the request delivers strings.
    request()->merge(['counter_presets' => ['5001', '10000']]);

    $html = (string) $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    expect($html)->toMatch('/value="1"\s+checked/')
        ->and($html)->not->toMatch('/value="custom"\s+checked/')
        // The custom inputs stay hidden for values belonging to a preset.
        ->and($html)->toMatch('/id="counter_presets_custom"\s+style="display: none;"/');
});

it('preselects custom and shows the inputs for values belonging to no preset', function () {
    request()->merge(['counter_presets' => ['42', '84']]);

    $html = (string) $this->blade('
        <x-lacodix-filter::model-filters model="'.Post::class.'" />
    ');

    expect($html)->toMatch('/value="custom"\s+checked/')
        ->and($html)->not->toMatch('/id="counter_presets_custom"\s+style="display: none;"/')
        ->and($html)->toContain('value="42"')
        ->and($html)->toContain('value="84"');
});
