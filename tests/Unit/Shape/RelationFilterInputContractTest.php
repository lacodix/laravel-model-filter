<?php

declare(strict_types=1);

use Lacodix\LaravelModelFilter\Enums\ValidationMode;
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
