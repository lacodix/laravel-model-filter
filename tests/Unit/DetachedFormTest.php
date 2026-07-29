<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Support\DetachedForm;

it('offers a namespaced id that no form is meant to carry', function () {
    expect(DetachedForm::id())->toBe('lmf-detached-controls')
        ->and(DetachedForm::id())->toStartWith('lmf-detached-');
});

it('does not derive the id from anything, so the markup stays deterministic', function () {
    // Neither randomness nor configuration: every process and every install renders the
    // same bytes, which response caches, ETags and downstream snapshots rely on - and no
    // application configuration ends up in the markup.
    $before = DetachedForm::id();

    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'app.name' => 'Something Else']);

    expect(DetachedForm::id())->toBe($before);
});
