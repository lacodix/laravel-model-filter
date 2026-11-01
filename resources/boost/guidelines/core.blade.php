## Laravel Model Filter

For Eloquent filtering, search, sorting, query-string scopes, filter forms, relations, or filter tests with `lacodix/laravel-model-filter`, use the `laravel-model-filter-development` Boost skill. If absent, see the [package docs](https://github.com/lacodix/laravel-model-filter/blob/master/README.md#laravel-boost-skill) and ask the user to enable the skill.

For model-owned per-field search predicates, implement `Contracts\SearchStrategy` in `searchable()`. Strategies must bind values, honor literal input and preserve scopes; callers may narrow fields but cannot override canonical strategies. See the skill and [search docs](https://github.com/lacodix/laravel-model-filter/blob/master/docs/basic-usage/search.md#custom-search-strategies).
