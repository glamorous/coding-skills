---
name: laravel-collections
description: Use when transforming arrays or reading values from associative arrays in PHP.
---

# Laravel Collections & associative array reads

## 1. `collect()` over native array functions

Always use `collect()` for array transformations. There are no native-array exceptions — even a single `map` is more readable as a Collection chain, and the rule should be uniform across the codebase so reviewers don't have to weigh whether a given case "qualifies".

```php
// Good
collect($items)
    ->map(fn (string $value): ?Status => Status::tryFrom((int) $value))
    ->filter()
    ->values()
    ->all();

// Bad
array_filter(array_map(
    fn (string $value): ?Status => Status::tryFrom((int) $value),
    $items,
));
```

### Closing the chain — prefer `->all()` over `->toArray()`

When the downstream code expects a plain `array`, end with `->all()`, not `->toArray()`:

- `->all()` returns the raw underlying items as a plain array — objects, models, DTOs stay intact.
- `->toArray()` walks the collection and recursively calls `toArray()` on every item. Eloquent models become attribute arrays, DTOs that implement `Arrayable` become arrays. That is almost never what you want unless you're explicitly serialising for output.

```php
// Good — items stay as objects/models
$users = collect($rows)
    ->map(fn (array $row): User => User::fromRow($row))
    ->all();

// Bad — User objects get flattened to attribute arrays
$users = collect($rows)
    ->map(fn (array $row): User => User::fromRow($row))
    ->toArray();
```

Use `->toArray()` only when you genuinely want deep array conversion (e.g. preparing data for `json_encode` or a response that expects arrays-all-the-way-down).

**Drop the closer entirely** when the consumer accepts a `Collection` directly — there's no value in converting back to a plain array just to hand off to code that would call `collect()` on it again.

## 2. `Arr::get()` over bracket access

For associative arrays, read values via `Illuminate\Support\Arr::get()` rather than bracket access. It returns `null` for missing keys instead of raising `Undefined array key`, supports dot-notation for nested reads, and reads more declaratively:

```php
use Illuminate\Support\Arr;

// Good
$email = Arr::get($payload, 'user.email');
$tag = Arr::get($attributes, 'tag', 'default');

// Bad
$email = $payload['user']['email'] ?? null;
$tag = $attributes['tag'] ?? 'default';
```

This applies to associative arrays (config blobs, parsed JSON, request payloads). For numerically-indexed arrays (`$items[0]`) bracket access is fine.
