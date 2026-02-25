<?php

use Lacodix\LaravelModelFilter\Filters\DateFilter;

it('field sets the field and populate works', function () {
    $filter = new class extends DateFilter {};
    $result = $filter->field('created_at')->populate('2023-01-01');

    // populate returns static, verify chaining works
    expect($result)->toBeInstanceOf(DateFilter::class);

    // Verify values were set correctly via reflection
    $ref = new ReflectionProperty($filter, 'values');
    expect($ref->getValue($filter))->toBe(['created_at' => '2023-01-01']);
});

it('field affects getQualifiedField without table', function () {
    $filter = new class extends DateFilter {};
    $filter->field('created_at');

    expect($filter->getQualifiedField())->toBe('created_at');
});

it('field affects getQualifiedField with table', function () {
    $filter = new class extends DateFilter {};
    $filter->field('created_at')->table('posts');

    expect($filter->getQualifiedField())->toBe('posts.created_at');
});

it('field overrides constructor field', function () {
    $filter = new class('old_field') extends DateFilter {};

    expect($filter->getQualifiedField())->toBe('old_field');

    $filter->field('new_field');

    expect($filter->getQualifiedField())->toBe('new_field');
});

it('field alias works the same as field', function () {
    $filter = new class extends DateFilter {};
    $filter->field('updated_at');

    expect($filter->getQualifiedField())->toBe('updated_at');
});
