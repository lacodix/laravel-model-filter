<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Exceptions\SearchInputException;
use Lacodix\LaravelModelFilter\Support\SearchInput;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Tests\Models\Comment;
use Tests\Models\Post;

dataset('literal search modes', [
    'equal' => [
        SearchMode::EQUAL,
        'Exact%_Value',
        'Exact%_Value',
        'ExactABValue',
    ],
    'like' => [
        SearchMode::LIKE,
        'need!le%_',
        'prefix Need!le%_ suffix',
        'prefix Need!leAB suffix',
    ],
    'starts with' => [
        SearchMode::STARTS_WITH,
        'need!le%_',
        'Need!le%_ suffix',
        'Need!leAB suffix',
    ],
    'ends with' => [
        SearchMode::ENDS_WITH,
        'need!le%_',
        'prefix Need!le%_',
        'prefix Need!leAB',
    ],
    'contains any' => [
        SearchMode::CONTAINS_ANY,
        'Missing need!le%_',
        'prefix Need!le%_ suffix',
        'prefix Need!leAB suffix',
    ],
    'contains all' => [
        SearchMode::CONTAINS_ALL,
        'first%_ second!%_',
        'First%_ middle Second!%_',
        'FirstAB middle Second!XY',
    ],
    'case-sensitive like' => [
        SearchMode::LIKE_CASE_SENSITIVE,
        "Needle'*?[x]",
        "prefix Needle'*?[x] suffix",
        "prefix Needle'ABCx suffix",
    ],
    'case-sensitive starts with' => [
        SearchMode::STARTS_WITH_CASE_SENSITIVE,
        "Needle'*?[x]",
        "Needle'*?[x] suffix",
        "Needle'ABCx suffix",
    ],
    'case-sensitive ends with' => [
        SearchMode::ENDS_WITH_CASE_SENSITIVE,
        "Needle'*?[x]",
        "prefix Needle'*?[x]",
        "prefix Needle'ABCx",
    ],
    'case-sensitive contains any' => [
        SearchMode::CONTAINS_ANY_CASE_SENSITIVE,
        "Missing Needle'*?[x]",
        "prefix Needle'*?[x] suffix",
        "prefix Needle'ABCx suffix",
    ],
    'case-sensitive contains all' => [
        SearchMode::CONTAINS_ALL_CASE_SENSITIVE,
        "First'*? Second[x]",
        "First'*? middle Second[x]",
        "First'AB middle Secondx",
    ],
]);

dataset('bound literal search grammars', [
    'MySQL case-insensitive' => [
        MySqlGrammar::class,
        SearchMode::LIKE,
        'MÜLLER!%_',
        'LOWER(`posts`.`title`) LIKE ? ESCAPE \'!\'',
        '%müller!!!%!_%',
    ],
    'MySQL case-sensitive' => [
        MySqlGrammar::class,
        SearchMode::LIKE_CASE_SENSITIVE,
        'Needle!%_',
        '`posts`.`title` LIKE BINARY ? ESCAPE \'!\'',
        '%Needle!!!%!_%',
    ],
    'PostgreSQL case-insensitive' => [
        PostgresGrammar::class,
        SearchMode::LIKE,
        'MÜLLER!%_',
        '"posts"."title"::text ILIKE ? ESCAPE \'!\'',
        '%müller!!!%!_%',
    ],
    'PostgreSQL case-sensitive' => [
        PostgresGrammar::class,
        SearchMode::LIKE_CASE_SENSITIVE,
        'Needle!%_',
        '"posts"."title"::text LIKE ? ESCAPE \'!\'',
        '%Needle!!!%!_%',
    ],
]);

dataset('bound legacy wildcard grammars', [
    'MySQL' => [
        MySqlGrammar::class,
        'LOWER(`posts`.`title`) LIKE ?',
        '%needle%_%',
    ],
    'PostgreSQL' => [
        PostgresGrammar::class,
        '"posts"."title"::text ILIKE ?',
        '%needle%_%',
    ],
]);

dataset('postgres like search modes', [
    SearchMode::LIKE,
    SearchMode::LIKE_CASE_SENSITIVE,
    SearchMode::STARTS_WITH,
    SearchMode::STARTS_WITH_CASE_SENSITIVE,
    SearchMode::ENDS_WITH,
    SearchMode::ENDS_WITH_CASE_SENSITIVE,
    SearchMode::CONTAINS_ANY,
    SearchMode::CONTAINS_ANY_CASE_SENSITIVE,
    SearchMode::CONTAINS_ALL,
    SearchMode::CONTAINS_ALL_CASE_SENSITIVE,
]);

dataset('sqlite case-sensitive search modes', [
    SearchMode::LIKE_CASE_SENSITIVE,
    SearchMode::STARTS_WITH_CASE_SENSITIVE,
    SearchMode::ENDS_WITH_CASE_SENSITIVE,
    SearchMode::CONTAINS_ANY_CASE_SENSITIVE,
    SearchMode::CONTAINS_ALL_CASE_SENSITIVE,
]);

it('treats wildcard characters literally for every search mode', function (
    SearchMode $mode,
    string $search,
    string $targetTitle,
    string $wildcardDecoyTitle
) {
    $target = Post::factory()->create(['title' => $targetTitle]);
    $wildcardDecoy = Post::factory()->create(['title' => $wildcardDecoyTitle]);

    $ids = Post::searchLiteral($search, ['title' => $mode])->pluck('id');

    expect($ids)
        ->toContain($target->id)
        ->not->toContain($wildcardDecoy->id);
})->with('literal search modes');

it('keeps wildcard matching enabled for the existing search scope', function () {
    $target = Post::factory()->create(['title' => 'Legacy%_Value']);
    $wildcardMatch = Post::factory()->create(['title' => 'LegacyABValue']);

    $legacyIds = Post::search('Legacy%_', ['title' => SearchMode::LIKE])->pluck('id');
    $literalIds = Post::searchLiteral('Legacy%_', ['title' => SearchMode::LIKE])->pluck('id');

    expect($legacyIds)
        ->toContain($target->id, $wildcardMatch->id)
        ->and($literalIds)
        ->toContain($target->id)
        ->not->toContain($wildcardMatch->id);
});

it('keeps the existing search scope behavior for the falsy string zero', function () {
    $withoutZero = Post::factory()->create([
        'title' => 'does not contain the digit',
        'content' => 'nothing numeric here',
    ]);
    $withZero = Post::factory()->create([
        'title' => 'contains 0',
        'content' => 'nothing else numeric here',
    ]);

    expect(Post::search('0')->pluck('id'))
        ->toContain($withoutZero->id, $withZero->id)
        ->and(Post::searchLiteral('0')->pluck('id'))
        ->toContain($withZero->id)
        ->not->toContain($withoutZero->id);
});

it('can enable literal wildcard handling for the existing search scope', function () {
    Config::set('model-filter.search_wildcards_as_literals', true);

    $target = Post::factory()->create(['title' => 'Configured%_Value']);
    $wildcardMatch = Post::factory()->create(['title' => 'ConfiguredABValue']);

    expect(Post::search('Configured%_', ['title' => SearchMode::LIKE])->pluck('id'))
        ->toContain($target->id)
        ->not->toContain($wildcardMatch->id);
});

it('parses string boolean values for wildcard configuration', function () {
    $target = Post::factory()->create(['title' => 'Configured%_Value']);
    $wildcardMatch = Post::factory()->create(['title' => 'ConfiguredABValue']);

    Config::set('model-filter.search_wildcards_as_literals', 'false');
    expect(Post::search('Configured%_', ['title' => SearchMode::LIKE])->pluck('id'))
        ->toContain($target->id, $wildcardMatch->id);

    Config::set('model-filter.search_wildcards_as_literals', 'true');
    expect(Post::search('Configured%_', ['title' => SearchMode::LIKE])->pluck('id'))
        ->toContain($target->id)
        ->not->toContain($wildcardMatch->id);
});

it('honors literal wildcard configuration for query-string searches', function () {
    Config::set('model-filter.search_wildcards_as_literals', true);
    $target = Post::factory()->create(['title' => 'Query%_Target']);
    $wildcardMatch = Post::factory()->create(['title' => 'QueryABTarget']);

    $request = Request::create('/posts', 'GET', [
        'search' => 'Query%_',
        'search_for' => 'title',
    ]);
    $this->app->instance('request', $request);

    expect(Post::query()->searchByQueryString()->pluck('id'))
        ->toContain($target->id)
        ->not->toContain($wildcardMatch->id);
});

it('keeps search input unlimited by default', function () {
    expect(SearchInput::isAllowed(str_repeat('word ', 1000)))->toBeTrue();
});

it('returns no results when the configured character limit is exceeded', function () {
    Config::set('model-filter.search_max_characters', 10);
    Post::factory()->create(['title' => 'elevenchars']);

    expect(SearchInput::isAllowed('elevenchars'))->toBeFalse()
        ->and(Post::search('elevenchars')->exists())->toBeFalse()
        ->and(Post::searchLiteral('elevenchars')->exists())->toBeFalse();
});

it('returns no results when the configured term limit is exceeded', function () {
    Config::set('model-filter.search_max_terms', 2);
    Post::factory()->create(['title' => 'first second third']);

    expect(SearchInput::isAllowed("first\n second\tthird"))->toBeFalse()
        ->and(Post::search('first second third')->exists())->toBeFalse()
        ->and(Post::searchLiteral('first second third')->exists())->toBeFalse();
});

it('can throw a diagnosable exception when a search limit is exceeded', function () {
    Config::set('model-filter.search_max_characters', 3);
    Config::set('model-filter.search_limit_exceeded_behavior', 'throw');

    expect(fn () => Post::searchLiteral('four')->exists())
        ->toThrow(SearchInputException::class, 'The search input exceeds a configured limit.');
});

it('rejects an unknown search limit behavior', function () {
    Config::set('model-filter.search_max_characters', 3);
    Config::set('model-filter.search_limit_exceeded_behavior', 'trow');

    expect(fn () => Post::searchLiteral('four')->exists())
        ->toThrow(
            InvalidArgumentException::class,
            'Invalid model-filter.search_limit_exceeded_behavior configuration. Expected "empty" or "throw".'
        );
});

it('allows search input at the configured limits', function () {
    Config::set('model-filter.search_max_characters', 12);
    Config::set('model-filter.search_max_terms', 2);
    $target = Post::factory()->create(['title' => 'first second']);

    expect(SearchInput::isAllowed('first second'))->toBeTrue()
        ->and(Post::searchLiteral('first second')->pluck('id'))
        ->toContain($target->id);
});

it('uses normalized unicode whitespace for contains searches', function () {
    $target = Post::factory()->create(['title' => 'foo between bar']);
    $decoy = Post::factory()->create(['title' => "foo\tbar"]);

    expect(Post::searchLiteral("foo\tbar", ['title' => SearchMode::CONTAINS_ALL])->pluck('id'))
        ->toContain($target->id, $decoy->id);
});

it('finds matching umlauts with sqlite case-insensitive search', function () {
    $mixedCaseTarget = Post::factory()->create(['title' => 'Müller-Kapelle']);
    $upperCaseTarget = Post::factory()->create(['title' => 'MÜLLER-CHOR']);

    expect(Post::searchLiteral('müller', ['title' => SearchMode::LIKE])->pluck('id'))
        ->toContain($mixedCaseTarget->id, $upperCaseTarget->id);
});

it('finds multi-character unicode case-fold equivalents with sqlite', function () {
    $lowerCaseTarget = Post::factory()->create(['title' => 'Straße']);
    $upperCaseTarget = Post::factory()->create(['title' => 'STRAẞE']);

    expect(Post::searchLiteral('straße', ['title' => SearchMode::LIKE])->pluck('id'))
        ->toContain($lowerCaseTarget->id, $upperCaseTarget->id);
});

it('binds legacy sqlite case-sensitive input instead of interpolating it', function (SearchMode $mode) {
    Post::factory()->count(2)->create();

    expect(Post::search('zzz*" OR 1=1 --', ['title' => $mode])->count())
        ->toBe(0);
})->with('sqlite case-sensitive search modes');

it('treats wildcard characters literally in nested fields', function () {
    $target = Comment::factory()
        ->for(Post::factory())
        ->state(['title' => 'Contact%_Mail'])
        ->create();
    $wildcardDecoy = Comment::factory()
        ->for(Post::factory())
        ->state(['title' => 'ContactABMail'])
        ->create();

    $ids = LiteralSearchableCommentPost::searchLiteral(
        'Contact%_',
        ['comments.title' => SearchMode::LIKE]
    )->pluck('id');

    expect($ids)
        ->toContain($target->post_id)
        ->not->toContain($wildcardDecoy->post_id);
});

it('generates bound literal patterns for each SQL grammar', function (
    string $grammarClass,
    SearchMode $mode,
    string $search,
    string $sqlFragment,
    string $binding
) {
    $connection = Post::query()->getConnection();
    $originalGrammar = $connection->getQueryGrammar();
    $connection->setQueryGrammar(new $grammarClass($connection));

    try {
        $query = Post::query();
        $mode->applyLiteralQuery($query, $query->qualifyColumn('title'), $search);

        expect($query->toSql())
            ->toContain($sqlFragment)
            ->and($query->getBindings())
            ->toBe([$binding]);
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }
})->with('bound literal search grammars');

it('keeps wildcard bindings on the non-literal MySQL and PostgreSQL paths', function (
    string $grammarClass,
    string $sqlFragment,
    string $binding
) {
    $connection = Post::query()->getConnection();
    $originalGrammar = $connection->getQueryGrammar();
    $connection->setQueryGrammar(new $grammarClass($connection));

    try {
        $query = Post::query();
        SearchMode::LIKE->applyQuery($query, $query->qualifyColumn('title'), 'Needle%_');

        expect($query->toSql())
            ->toContain($sqlFragment)
            ->not->toContain('ESCAPE')
            ->and($query->getBindings())
            ->toBe([$binding]);
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }
})->with('bound legacy wildcard grammars');

it('casts PostgreSQL non-text fields for every like search mode', function (SearchMode $mode) {
    $connection = Post::query()->getConnection();
    $originalGrammar = $connection->getQueryGrammar();
    $connection->setQueryGrammar(new PostgresGrammar($connection));

    try {
        $query = Post::query();
        $mode->applyQuery($query, $query->qualifyColumn('counter'), '12 34');

        expect($query->toSql())->toContain('"posts"."counter"::text');
    } finally {
        $connection->setQueryGrammar($originalGrammar);
    }
})->with('postgres like search modes');

class LiteralSearchableCommentPost extends Model
{
    use IsSearchable;

    protected $table = 'posts';

    protected array $searchable = [
        'comments.title',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
