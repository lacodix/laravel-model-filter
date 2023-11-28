---
title: Filter for Relations
weight: 4
---

You can create a simple Relation filter by extending the base SelectFilter. In the following example we create
a Tag-Filter that can be applied to all models that have a tag relation.

Thanks to [hfmiguel](https://github.com/hfmiguel) for this example.

```php
class TagsFilter extends SelectInFilter
{
    public FilterMode $mode = FilterMode::EQUAL;

    protected string $field = 'tag_id';  /**  column of the relation */ 
    protected string $relation = 'tags';  /** relation name : where the search will be applied */

    public function title(): string
    {
        return __('Tags');
    }

    public function component(): string
    {
        return 'select';
    }

    public function options(): array
    {
        return Tag::query()
            ->orderBy('name')
            ->pluck('id', 'name')
            ->toArray();
    }
    
    public function apply(Builder $query): Builder
    {
        return $query->whereHas($this->relation, function (Builder $query) {
            $query->whereIn($this->field, $this->values);
        });
    }
}
```
