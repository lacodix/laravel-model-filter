<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Lacodix\LaravelModelFilter\Contracts\SearchStrategy;
use Lacodix\LaravelModelFilter\Enums\SearchMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Exceptions\SearchInputException;
use Lacodix\LaravelModelFilter\Traits\IsSearchable;
use Tests\Models\Comment;
use Tests\Models\Post;
use Tests\Models\Tag;

it('searches a logical field through a prepared bound mapped predicate', function () {
    $target = Post::factory()->create(['title' => 'opaque', 'content' => 'alice@example.test']);
    Post::factory()->create(['title' => 'ALICE@example.test', 'content' => 'other']);

    $query = StrategyPost::search(' ALICE@example.test ');

    expect($query->pluck('id')->all())->toBe([$target->id])
        ->and($query->getBindings())->toBe(['alice@example.test']);
});

it('ignores null caller field selections normalized from empty query string values', function () {
    $target = Post::factory()->create(['title' => 'needle', 'content' => 'other']);
    Post::factory()->create(['title' => 'other', 'content' => 'needle']);
    Route::get('/strategy-search', function () {
        expect(request()->query('search_for'))->toBe([null, 'title']);

        return Post::searchByQueryString()->pluck('id')->all();
    })->middleware(ConvertEmptyStringsToNull::class);

    $this->getJson('/strategy-search?search=needle&search_for[]=&search_for[]=title')
        ->assertOk()
        ->assertExactJson([$target->id]);
});

it('preserves the canonical strategy when callers select fields or override modes', function ($selection) {
    $target = Post::factory()->create(['title' => 'opaque', 'content' => 'alice@example.test']);
    Post::factory()->create(['title' => 'ALICE@example.test', 'content' => 'other']);

    expect(StrategyPost::search('ALICE@example.test', $selection)->pluck('id')->all())
        ->toBe([$target->id]);
})->with([
    'single field' => ['title'],
    'numeric field list' => [['title']],
    'null and custom field' => [[null, 'title']],
    'enum override' => [['title' => SearchMode::EQUAL]],
    'string override' => [['title' => 'like']],
]);

it('preserves strategies through resolved field round trips', function () {
    $target = Post::factory()->create(['title' => 'opaque', 'content' => 'alice@example.test']);
    Post::factory()->create(['title' => 'ALICE@example.test', 'content' => 'other']);
    $resolved = (new StrategyPost)->searchableFields()->only(['title'])->all();

    expect(StrategyPost::search('ALICE@example.test', $resolved)->pluck('id')->all())->toBe([$target->id]);
});

it('ORs each compound root predicate without weakening outer or global scopes', function () {
    $ordinary = Post::factory()->create(['type' => 'needle', 'content' => 'other', 'published' => false, 'counter' => 1]);
    $custom = Post::factory()->create(['type' => 'post', 'content' => 'needle', 'published' => true, 'counter' => 2]);
    $alternative = Post::factory()->create(['type' => 'post', 'content' => 'other', 'published' => false, 'counter' => 99]);
    Post::factory()->create(['type' => 'post', 'content' => 'needle', 'published' => false, 'counter' => 3]);
    Post::factory()->create(['type' => 'post', 'content' => 'needle', 'published' => true, 'counter' => 100]);
    Post::factory()->create(['type' => 'hidden', 'content' => 'needle', 'published' => true, 'counter' => 4]);

    expect(CompoundStrategyPost::where('counter', '<', 100)->search('needle')->orderBy('id')->pluck('id')->all())
        ->toBe([$ordinary->id, $custom->id, $alternative->id]);
});

it('rejects caller supplied strategies for ordinary fields', function () {
    $injected = new NormalizedSearchStrategy;

    expect(fn () => Post::search('needle', ['title' => $injected])->get())
        ->toThrow(InvalidArgumentException::class, 'Search strategies must be declared on the model.');
});

it('rejects object and array entries in caller field lists', function (array $selection) {
    expect(fn () => Post::search('needle', $selection)->get())
        ->toThrow(InvalidArgumentException::class);
})->with([
    'object entry' => fn () => [new NormalizedSearchStrategy],
    'array entry' => fn () => [['title']],
]);

it('rejects invalid model strategy declarations clearly', function ($declaration) {
    $model = new ConfiguredStrategyPost;
    $model->declaration = $declaration;

    expect(fn () => $model->searchableFields())
        ->toThrow(InvalidArgumentException::class);
})->with([
    'object without contract' => fn () => ['title' => new stdClass],
    'array configuration' => [['title' => ['strategy' => 'unknown']]],
    'invalid scalar' => [['title' => 123]],
    'unkeyed strategy' => fn () => [new NormalizedSearchStrategy],
    'unkeyed null' => [[null]],
]);

it('keeps canonical strategies through query string mode overrides', function () {
    $target = Post::factory()->create(['title' => 'opaque', 'content' => 'alice@example.test']);
    Post::factory()->create(['title' => 'ALICE@example.test', 'content' => 'other']);
    $this->app->instance('request', Request::create('/posts', 'GET', [
        'search' => 'ALICE@example.test',
        'search_for' => ['title' => 'equal'],
    ]));

    expect(StrategyPost::searchByQueryString()->pluck('id')->all())->toBe([$target->id]);
});

it('never executes caller supplied strategies for hidden fields or canonical replacements', function () {
    $injected = new NormalizedSearchStrategy;
    $target = Post::factory()->create(['title' => 'opaque', 'content' => 'alice@example.test']);
    $decoy = Post::factory()->create(['title' => 'ALICE@example.test', 'content' => 'other']);

    expect(StrategyPost::search('ALICE@example.test', ['title' => $injected])->pluck('id')->all())
        ->toBe([$target->id]);
    expect(StrategyPost::search('missing', ['hidden' => $injected])->orderBy('id')->pluck('id')->all())
        ->toBe([$target->id, $decoy->id]);
    expect($injected->calls)->toBe([]);
});

it('keeps legacy ordinary mode overrides and unknown string fallback', function () {
    $target = Post::factory()->create(['title' => 'needle', 'content' => 'other']);
    $partial = Post::factory()->create(['title' => 'prefix needle', 'content' => 'other']);

    expect(Post::search('needle', ['title' => 'equal'])->pluck('id')->all())->toBe([$target->id]);
    expect(Post::search('needle', [null, 'title' => 'equal'])->pluck('id')->all())->toBe([$target->id]);
    expect(Post::search('needle', ['title' => 'unknown-mode'])->orderBy('id')->pluck('id')->all())
        ->toBe([$target->id, $partial->id]);
    expect(Post::search('needle', ['title' => NormalizedSearchStrategy::class])->orderBy('id')->pluck('id')->all())
        ->toBe([$target->id, $partial->id]);
});

it('passes qualified nested fields while preserving scoped relation predicates', function (string $scope) {
    $ordinary = Tag::factory()->create(['title' => 'needle']);
    $target = Tag::factory()->create(['title' => 'mapped']);
    $outside = Tag::factory()->create(['title' => 'outside']);
    $wrongPost = Tag::factory()->create(['title' => 'wrong post']);
    $wrongComment = Tag::factory()->create(['title' => 'wrong comment']);

    foreach ([[$target, true, true, 1], [$outside, true, true, 1], [$wrongPost, false, true, 1], [$wrongComment, true, false, 99]] as [$tag, $postPublished, $commentPublished, $counter]) {
        $post = Post::factory()->create(['published' => $postPublished]);
        $tag->posts()->attach($post);
        Comment::factory()->for($post)->create([
            'title' => 'opaque', 'content' => 'needle', 'published' => $commentPublished, 'counter' => $counter,
        ]);
    }

    $query = StrategyTag::where('tags.title', '!=', 'outside')->{$scope}('NEEDLE', [
        'title' => 'like', 'posts.approvedComments.title' => 'like',
    ]);

    expect($query->orderBy('tags.id')->pluck('tags.id')->all())->toBe([$ordinary->id, $target->id]);
    expect($query->toSql())->toContain('"comments"."content" = ?');
})->with(['search', 'searchLiteral']);

it('dispatches bound literal predicates for explicit and configured literal searches', function (string $scope, bool $configured, string $dispatch) {
    Config::set('model-filter.search_wildcards_as_literals', $configured);
    $strategy = new NormalizedSearchStrategy;
    $model = new ConfiguredStrategyPost;
    $model->declaration = ['title' => $strategy];
    $search = "O'Reilly%_*?[x]\" OR 1=1 --";
    $target = Post::factory()->create(['title' => 'opaque', 'content' => "o'reilly%_*?[x]\" or 1=1 --"]);
    Post::factory()->create(['title' => 'opaque', 'content' => 'other']);

    $query = $model->newQuery()->{$scope}($search);

    expect($query->pluck('id')->all())->toBe([$target->id]);
    expect($query->getBindings())->toBe(["o'reilly%_*?[x]\" or 1=1 --"]);
    expect($query->toSql())->not->toContain($search);
    expect($strategy->calls)->toBe([['posts.title', $search, $dispatch]]);
})->with([
    'ordinary' => ['search', false, 'ordinary'],
    'explicit literal' => ['searchLiteral', false, 'literal'],
    'configured literal' => ['search', true, 'literal'],
]);

it('does not execute excluded model strategies', function () {
    $strategy = new NormalizedSearchStrategy;
    $model = new ConfiguredStrategyPost;
    $model->declaration = ['title' => $strategy, 'type'];
    $target = Post::factory()->create(['type' => 'needle']);

    expect($model->newQuery()->search('needle', ['type'])->pluck('id')->all())->toBe([$target->id]);
    expect($strategy->calls)->toBe([]);
});

it('does not execute strategies for empty or legacy falsy input', function (string $scope, ?string $search) {
    $strategy = new NormalizedSearchStrategy;
    $model = new ConfiguredStrategyPost;
    $model->declaration = ['title' => $strategy];
    $target = Post::factory()->create();

    expect($model->newQuery()->{$scope}($search)->pluck('id')->all())->toBe([$target->id]);
    expect($strategy->calls)->toBe([]);
})->with([
    ['search', null], ['search', '  '], ['search', '0'],
    ['searchLiteral', null], ['searchLiteral', '  '],
]);

it('searches literal zero through the strategy', function () {
    $target = Post::factory()->create(['content' => '0']);
    Post::factory()->create(['content' => 'other']);

    expect(StrategyPost::searchLiteral('0')->pluck('id')->all())->toBe([$target->id]);
});

it('does not execute strategies when input exceeds configured limits', function (string $scope, string $limit, string $behavior) {
    Config::set('model-filter.'.$limit, 1);
    Config::set('model-filter.search_limit_exceeded_behavior', $behavior);
    $strategy = new NormalizedSearchStrategy;
    $model = new ConfiguredStrategyPost;
    $model->declaration = ['title' => $strategy];
    Post::factory()->create(['content' => 'two terms']);

    if ($behavior === 'throw') {
        expect(fn () => $model->newQuery()->{$scope}('two terms')->get())->toThrow(SearchInputException::class);
    } else {
        expect($model->newQuery()->{$scope}('two terms')->exists())->toBeFalse();
    }
    expect($strategy->calls)->toBe([]);
})->with(['search', 'searchLiteral'])
    ->with(['search_max_characters', 'search_max_terms'])
    ->with(['empty', 'throw']);

it('honors model field name narrowing before executing custom strategies', function () {
    $strategy = new NormalizedSearchStrategy;
    $model = new HiddenStrategyPost;
    $model->declaration = ['title' => $strategy, 'type'];
    $target = Post::factory()->create(['type' => 'needle', 'content' => 'other']);
    Post::factory()->create(['type' => 'other', 'content' => 'needle']);

    expect($model->newQuery()->search('needle')->pluck('id')->all())->toBe([$target->id]);
    expect($model->newQuery()->search('needle', ['title' => 'equal'])->count())->toBe(2);
    expect($strategy->calls)->toBe([]);
});

class NormalizedSearchStrategy implements SearchStrategy
{
    public array $calls = [];

    public function applyQuery(Builder $query, string $field, string $search): Builder
    {
        $this->calls[] = [$field, $search, 'ordinary'];

        return $query->where(str_replace('.title', '.content', $field), mb_strtolower($search));
    }

    public function applyLiteralQuery(Builder $query, string $field, string $search): Builder
    {
        $this->calls[] = [$field, $search, 'literal'];

        return $query->where(str_replace('.title', '.content', $field), mb_strtolower($search));
    }
}

class StrategyPost extends Model
{
    use IsSearchable;

    protected $table = 'posts';

    public function searchable(): array
    {
        return ['title' => new NormalizedSearchStrategy];
    }
}

class CompoundSearchStrategy extends NormalizedSearchStrategy
{
    public function applyQuery(Builder $query, string $field, string $search): Builder
    {
        return parent::applyQuery($query, $field, $search)
            ->where($query->qualifyColumn('published'), true)
            ->orWhere($query->qualifyColumn('counter'), 99);
    }
}

class CompoundStrategyPost extends StrategyPost
{
    protected static function booted(): void
    {
        static::addGlobalScope('visible', fn (Builder $query) => $query->where('posts.type', '!=', 'hidden'));
    }

    public function searchable(): array
    {
        return ['type' => SearchMode::EQUAL, 'title' => new CompoundSearchStrategy];
    }
}

class ConfiguredStrategyPost extends StrategyPost
{
    public array $declaration = [];

    public function searchable(): array
    {
        return $this->declaration;
    }
}

class StrategyTag extends Model
{
    use IsSearchable;

    protected $table = 'tags';

    public function searchable(): array
    {
        return ['title' => SearchMode::EQUAL, 'posts.approvedComments.title' => new CompoundSearchStrategy];
    }

    public function posts()
    {
        return $this->belongsToMany(StrategyRelatedPost::class, 'post_tag', 'tag_id', 'post_id')
            ->where('posts.published', true);
    }
}

class StrategyRelatedPost extends Post
{
    protected $table = 'posts';

    public function approvedComments()
    {
        return $this->hasMany(Comment::class, 'post_id')->where('comments.published', true);
    }
}

class HiddenStrategyPost extends ConfiguredStrategyPost
{
    public function searchableFieldNames(): array
    {
        return ['type'];
    }
}
