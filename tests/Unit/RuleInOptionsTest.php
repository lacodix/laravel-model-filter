<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Tests\Filters\TagTimeframeFilter;
use Tests\Models\Post;

/**
 * Option values are free text once they come from an autocomplete or a
 * prepopulated column: "Trompete, Flügelhorn", a quote, a leading space. A
 * rule built as the string 'in:a,b' goes through str_getcsv() in Laravel's
 * rule parser, which splits at the comma, strips the quotes and trims the
 * items — the filter then fails validation silently and is skipped.
 */
const AWKWARD_OPTIONS = [
    'Comma' => 'a,b',
    'Leading quote' => '"quoted',
    'Leading space' => ' lead',
    'Plain' => 'plain',
];

function awkwardSelect(FilterMode $mode = FilterMode::EQUAL): SelectFilter
{
    return (new SelectFilter('type'))
        ->setQueryName('awkward')
        ->setMode($mode)
        ->setOptions(AWKWARD_OPTIONS);
}

it('demonstrates why an in: string cannot carry such values', function () {
    // The former rule 'in:a,b,"quoted, lead,plain': str_getcsv() turns 'a,b'
    // into two items, reads the leading quote as an enclosure that swallows
    // the following items, and Laravel trims the leading space.
    $rule = 'in:'.implode(',', AWKWARD_OPTIONS);

    expect(str_getcsv(substr($rule, 3)))->toBe(['a', 'b', 'quoted, lead,plain'])
        ->and(Validator::make(['v' => 'a,b'], ['v' => $rule])->fails())->toBeTrue()
        ->and(Validator::make(['v' => '"quoted'], ['v' => $rule])->fails())->toBeTrue()
        ->and(Validator::make(['v' => ' lead'], ['v' => $rule])->fails())->toBeTrue()
        ->and(Validator::make(['v' => 'plain'], ['v' => $rule])->fails())->toBeTrue()
        ->and(Validator::make(['v' => 'a'], ['v' => $rule])->fails())->toBeFalse();

    // Rule::in() quotes every value, so the parser hands the exact values back.
    $ruleObject = (string) \Illuminate\Validation\Rule::in(AWKWARD_OPTIONS);

    expect($ruleObject)->toBe('in:"a,b","""quoted"," lead","plain"')
        ->and(Validator::make(['v' => 'a,b'], ['v' => $ruleObject])->fails())->toBeFalse()
        ->and(Validator::make(['v' => 'plain'], ['v' => $ruleObject])->fails())->toBeFalse()
        ->and(Validator::make(['v' => 'a'], ['v' => $ruleObject])->fails())->toBeTrue();
});

it('builds the select rules as Rule::in objects', function () {
    expect(awkwardSelect()->rules()['awkward'])->toBeInstanceOf(In::class)
        ->and((string) awkwardSelect()->rules()['awkward'])->toBe('in:"a,b","""quoted"," lead","plain"');

    $multi = awkwardSelect(FilterMode::CONTAINS)->rules();

    expect($multi['awkward'])->toBe('array')
        ->and($multi['awkward.*'])->toBeInstanceOf(In::class);

    // The nullable "none" entry stays part of the rule.
    expect((string) awkwardSelect()->nullable()->rules()['awkward'])->toContain('"__null__"');
});

it('accepts option values with a comma, a quote or a leading space', function (string $value) {
    $filter = awkwardSelect()->populate($value);

    expect($filter->fails())->toBeFalse()
        ->and($filter->validate())->toBe(['awkward' => $value])
        ->and($filter->apply(Post::query()))
        ->toHaveSqlEquals('select * from "posts" where "type" = ?', [$value]);
})->with(['a,b', '"quoted', ' lead', 'plain']);

it('still rejects values outside the options', function (string $value) {
    expect(awkwardSelect()->populate($value)->fails())->toBeTrue();
})->with(['a', 'b', 'lead', 'quoted', '"quoted"', 'quoted, lead,plain']);

it('accepts such values in the multi modes', function () {
    $filter = awkwardSelect(FilterMode::CONTAINS)->populate(['a,b', '"quoted']);

    expect($filter->fails())->toBeFalse()
        ->and($filter->apply(Post::query()))
        ->toHaveSqlEquals('select * from "posts" where "type" in (?, ?)', ['a,b', '"quoted']);

    expect(awkwardSelect(FilterMode::CONTAINS)->populate(['a,b', 'a'])->fails())->toBeTrue();
});

it('applies such values through the model scope instead of skipping the filter silently', function () {
    Post::factory()->state(['type' => 'a,b'])->count(2)->create();
    Post::factory()->state(['type' => 'plain'])->count(3)->create();

    $filter = awkwardSelect();

    $query = Post::query();
    $filter->populateFromScope('a,b');

    expect($filter->fails())->toBeFalse()
        ->and($filter->apply($query)->count())->toBe(2);
});

it('builds the timeframe value rules as Rule::in objects and accepts awkward option values', function () {
    $single = (new TagTimeframeFilter)
        ->setQueryName('tags_timeframe')
        ->setMode(FilterMode::EQUAL)
        ->setOptions(AWKWARD_OPTIONS);

    expect($single->rules()['tags_timeframe.values'])->toBeInstanceOf(In::class)
        ->and((string) $single->rules()['tags_timeframe.values'])->toBe('in:"a,b","""quoted"," lead","plain"');

    expect($single->populate(['values' => 'a,b', 'mode' => 'current'])->fails())->toBeFalse()
        ->and($single->populate(['values' => 'a', 'mode' => 'current'])->fails())->toBeTrue();

    $multi = (new TagTimeframeFilter)
        ->setQueryName('tags_timeframe')
        ->setMode(FilterMode::CONTAINS)
        ->setOptions(AWKWARD_OPTIONS);

    expect($multi->rules()['tags_timeframe.values'])->toBe('array')
        ->and($multi->rules()['tags_timeframe.values.*'])->toBeInstanceOf(In::class)
        ->and($multi->populate(['values' => ['a,b', ' lead'], 'mode' => 'current'])->fails())->toBeFalse()
        ->and($multi->populate(['values' => ['a,b', 'lead'], 'mode' => 'current'])->fails())->toBeTrue();
});
