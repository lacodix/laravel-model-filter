<?php

use Lacodix\LaravelModelFilter\Enums\FilterMode;
use Lacodix\LaravelModelFilter\Exceptions\InvalidArgumentException;
use Lacodix\LaravelModelFilter\Filters\EnumFilter;
use Lacodix\LaravelModelFilter\Filters\OptionFilter;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;
use Lacodix\LaravelModelFilter\Filters\StringFilter;
use Tests\Models\Post;

enum _MetaKind: string
{
    case Page = 'page';
    case Post = 'post';
}

function metaSelectFilter(): SelectFilter
{
    return (new SelectFilter('type'))
        ->setQueryName('type_meta')
        ->setOptions([
            'Page' => 'page',
            'Post' => 'post',
        ]);
}

beforeEach(function () {
    Post::factory()->state(['type' => 'page'])->count(3)->create();
    Post::factory()->state(['type' => 'post'])->count(2)->create();
});

it('has no meta by default', function () {
    expect((new StringFilter('title'))->getMeta())->toEqual([])
        ->and(metaSelectFilter()->getOptionMeta())->toEqual([])
        ->and(metaSelectFilter()->optionMetaFor('page'))->toEqual([]);
});

it('stores free meta on every filter type and merges repeated calls recursively', function () {
    $filter = (new StringFilter('title'))
        ->meta(['presentation' => 'avatars', 'ui' => ['columns' => 4, 'dense' => true]])
        ->meta(['ui' => ['columns' => 6], 'hint' => 'People']);

    expect($filter->getMeta())->toEqual([
        'presentation' => 'avatars',
        'ui' => ['columns' => 6, 'dense' => true],
        'hint' => 'People',
    ]);
});

it('does not interpret meta while filtering', function () {
    $filter = metaSelectFilter()->meta(['presentation' => 'avatars']);

    expect($filter->populate('page')->apply(Post::query())->count())->toEqual(3)
        ->and((string) $filter->rules()['type_meta'])->toEqual('in:"page","post"');
});

it('stores option meta per option value and returns an empty array for unknown values', function () {
    $filter = metaSelectFilter()->optionMeta([
        'page' => ['avatar' => 'https://example.test/page.png', 'subtitle' => 'Pages'],
        'post' => ['initials' => 'PO'],
    ]);

    expect($filter->getOptionMeta())->toEqual([
        'page' => ['avatar' => 'https://example.test/page.png', 'subtitle' => 'Pages'],
        'post' => ['initials' => 'PO'],
    ])
        ->and($filter->optionMetaFor('page'))->toEqual(['avatar' => 'https://example.test/page.png', 'subtitle' => 'Pages'])
        ->and($filter->optionMetaFor('post'))->toEqual(['initials' => 'PO'])
        ->and($filter->optionMetaFor('draft'))->toEqual([])
        ->and($filter->optionMetaFor(null))->toEqual([])
        ->and($filter->optionMetaFor(['page']))->toEqual([]);
});

it('looks option meta up by the stringified value and by backed enum cases', function () {
    $filter = (new SelectFilter('user_id'))
        ->setOptions(['Ada' => 7, 'Grace' => 12])
        ->optionMeta([
            7 => ['initials' => 'AL'],
            '12' => ['initials' => 'GH'],
        ]);

    expect($filter->optionMetaFor(7))->toEqual(['initials' => 'AL'])
        ->and($filter->optionMetaFor('7'))->toEqual(['initials' => 'AL'])
        ->and($filter->optionMetaFor(12))->toEqual(['initials' => 'GH'])
        ->and($filter->optionMetaFor('12'))->toEqual(['initials' => 'GH']);

    $enumFilter = (new EnumFilter('type'))
        ->setEnum(_MetaKind::class)
        ->optionMeta(['page' => ['initials' => 'PG']]);

    expect($enumFilter->optionMetaFor(_MetaKind::Page))->toEqual(['initials' => 'PG'])
        ->and($enumFilter->optionMetaFor(_MetaKind::Post))->toEqual([]);
});

it('resolves closure option meta lazily and only once per instance', function () {
    $calls = 0;
    $filter = metaSelectFilter()->optionMeta(function (SelectFilter $filter) use (&$calls): array {
        $calls++;

        return collect($filter->options())
            ->mapWithKeys(fn (string $value, string $label) => [$value => ['initials' => strtoupper(substr($label, 0, 2))]])
            ->all();
    });

    expect($calls)->toBe(0);

    expect($filter->optionMetaFor('page'))->toEqual(['initials' => 'PA'])
        ->and($filter->optionMetaFor('post'))->toEqual(['initials' => 'PO'])
        ->and($filter->getOptionMeta())->toHaveCount(2)
        ->and($calls)->toBe(1);
});

it('replaces option meta on repeated calls', function () {
    $filter = metaSelectFilter()
        ->optionMeta(['page' => ['initials' => 'PA']])
        ->optionMeta(['post' => ['initials' => 'PO']]);

    expect($filter->getOptionMeta())->toEqual(['post' => ['initials' => 'PO']]);
});

it('throws for option meta entries that are not arrays', function (array|Closure $meta) {
    metaSelectFilter()->optionMeta($meta)->getOptionMeta();
})->throws(InvalidArgumentException::class)->with([
    'string entry' => [['page' => 'avatar.png']],
    'closure with scalar entry' => [fn () => ['page' => 'avatar.png']],
]);

it('lets class based filters define their option meta through a hook', function () {
    $filter = new class extends SelectFilter {
        protected string $field = 'type';

        protected array $meta = ['presentation' => 'avatars'];

        public function options(): array
        {
            return ['Page' => 'page', 'Post' => 'post'];
        }

        protected function defineOptionMeta(): array
        {
            return ['page' => ['initials' => 'PG']];
        }
    };

    expect($filter->getMeta())->toEqual(['presentation' => 'avatars'])
        ->and($filter->optionMetaFor('page'))->toEqual(['initials' => 'PG'])
        // The hook takes over entirely, like definePresets() does.
        ->and($filter->optionMeta(['post' => ['initials' => 'PO']])->optionMetaFor('post'))->toEqual([]);
});

it('carries option meta on option filters', function () {
    $filter = (new OptionFilter(['published', 'featured']))
        ->optionMeta(['published' => ['color' => '#0f0']]);

    expect($filter->optionMetaFor('published'))->toEqual(['color' => '#0f0'])
        ->and($filter->optionMetaFor('featured'))->toEqual([]);
});

it('resolves option meta afresh on a preparation instance', function () {
    $calls = 0;
    $filter = metaSelectFilter()->optionMeta(function (SelectFilter $filter) use (&$calls): array {
        $calls++;

        return ['page' => ['model' => $filter->model()?->getTable()]];
    });

    // Resolved against a bound model ...
    expect($filter->setModel(new Post())->optionMetaFor('page'))->toEqual(['model' => 'posts'])
        ->and($calls)->toBe(1);

    // ... the preparation instance (model reset) resolves its own map, once.
    $prepared = $filter->newPreparationInstance();

    expect($prepared->optionMetaFor('page'))->toEqual(['model' => null])
        ->and($prepared->optionMetaFor('page'))->toEqual(['model' => null])
        ->and($calls)->toBe(2)
        // The original keeps its resolved map.
        ->and($filter->optionMetaFor('page'))->toEqual(['model' => 'posts'])
        ->and($calls)->toBe(2);
});

it('keeps meta on prepared and multi mode filters without touching the query string', function () {
    $filter = metaSelectFilter()
        ->setMode(FilterMode::CONTAINS)
        ->meta(['presentation' => 'avatars'])
        ->optionMeta(fn () => ['page' => ['initials' => 'PG']]);

    $prepared = $filter->newPreparationInstance();

    expect($prepared->getMeta())->toEqual(['presentation' => 'avatars'])
        ->and($prepared->optionMetaFor('page'))->toEqual(['initials' => 'PG'])
        ->and($prepared->getValues())->toEqual([])
        ->and($filter->populate(['page', 'post'])->apply(Post::query())->count())->toEqual(5)
        ->and($filter->getValue())->toEqual(['page', 'post']);
});
