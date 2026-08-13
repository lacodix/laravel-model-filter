<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Enums\ValidationMode;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Traits\RunsOnRelation;
use Tests\Filters\CommentAuthorNameFilter;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Support\InputContractTestSupport;

dataset('relation input validation modes', [
    'filter' => ValidationMode::FILTER,
    'throw' => ValidationMode::THROW,
]);

it('guards direct relation-filter application', function (ValidationMode $validationMode) {
    $query = Comment::query();
    $before = InputContractTestSupport::snapshot($query);
    $filter = (new CommentAuthorNameFilter)
        ->setModel(new Comment)
        ->setValidationMode($validationMode)
        ->populate([['Post A']]);

    if ($validationMode === ValidationMode::THROW) {
        $exception = InputContractTestSupport::validationException(
            fn () => InputContractTestSupport::withPhpWarningsAsExceptions(
                fn () => $filter->apply($query)
            )
        );

        expect(array_keys($exception->errors()))->toContain('comment_author_name_filter');
    } else {
        InputContractTestSupport::withPhpWarningsAsExceptions(fn () => $filter->apply($query));
    }

    expect(InputContractTestSupport::snapshot($query))->toBe($before);
})->with('relation input validation modes');

it('preserves the historical relation query for an absent direct value', function () {
    $query = Comment::query();

    (new CommentAuthorNameFilter)
        ->setModel(new Comment)
        ->populate(null)
        ->apply($query);

    expect(strtolower($query->toSql()))->toContain('exists')
        ->and($query->getBindings())->toBe(['%%']);
});

it('keeps absent direct multi-select relation filters safe', function (bool $populateNull) {
    $query = Comment::query();
    $filter = (new class extends SelectFilter {
        use RunsOnRelation;

        protected string $relation = 'post';
        protected string $field = 'type';
    })
        ->setMode(FilterMode::CONTAINS)
        ->setOptions(['Page' => 'page'])
        ->setModel(new Comment);

    if ($populateNull) {
        $filter->populate(null);
    }

    InputContractTestSupport::withPhpWarningsAsExceptions(fn () => $filter->apply($query));

    expect(strtolower($query->toSql()))->toContain('exists', '0 = 1')
        ->and($query->getBindings())->toBe([]);
})->with([
    'never populated' => false,
    'populated with null' => true,
]);

it('preserves valid direct relation-filter results', function () {
    $matchingPost = Post::factory()->create(['title' => 'Post A']);
    $otherPost = Post::factory()->create(['title' => 'Post B']);
    $matchingComments = Comment::factory()->count(2)->create(['post_id' => $matchingPost->id]);
    Comment::factory()->create(['post_id' => $otherPost->id]);
    $query = Comment::query();

    (new CommentAuthorNameFilter)
        ->setModel(new Comment)
        ->populate('Post A')
        ->apply($query);

    expect($query->pluck('id')->all())->toBe($matchingComments->pluck('id')->all());
});
