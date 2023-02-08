---
title: Tweak Filter Behaviour
weight: 2
---

Sometimes you don't need a direct match between filter values and database. For
example if you want a SelectFilter for Verified and Unverified users, but the
database doesn't contain a bool or string-value but kind of an email_verified_at
date field that implicit means a user is verified if this field is not null.

You can extend the available filters with own query logic. First create a SelectFilter
as usual.

```bash
php artisan make:filter UserVerified -t select -f email_verified_at
```

Then add option values like usual and additionally extend the filters query function

```php
<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lacodix\LaravelModelFilter\Filters\SelectFilter;

class UserVerifiedFilter extends SelectFilter
{
    protected string $field = 'email_verified_at';

    public function options(): array
    {
        return [
            'verified',
            'unverified',
        ];
    }

    public function apply(Builder $query): Builder
    {
        return match($this->values[$this->field]) {
            'verified' => $query->whereNotNull($this->field),
            'unverified' => $query->whereNull($this->field),
            default => $query,
        };
    }
}
```
