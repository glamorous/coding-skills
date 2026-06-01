---
name: laravel-resources
description: Use when creating or modifying Laravel API Resource classes (JsonResource).
---

# Laravel API Resources

## 1. Class shape

Extend `JsonResource`. Add a `@mixin` PHPDoc tag pointing at the resource model so IDEs and PHPStan can resolve `$this->name`, `$this->relationships`, etc.

```php
/**
 * @mixin Article
 */
class ArticleResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray($request): array
    {
        return [
            'identifier' => $this->identifier,
            'title' => $this->title,
            'created_at' => $this->created_at->toAtomString(),
        ];
    }
}
```

## 2. Identifiers in payloads

Always emit `identifier` (the ULID), never `id`. See `laravel-models` for the wider rule.

## 3. Timestamps in payloads

Always ATOM / RFC 3339 via Carbon's `->toAtomString()`:

```php
'created_at' => $this->created_at->toAtomString(),
'resolved_at' => $this->resolved_at?->toAtomString(),
```

Never use the display helpers (`format_datetime`, `format_date`) — they are for the UI, not for machine-readable output. See `laravel-architecture` for the helpers.

## 4. Conditional relationships — `whenLoaded()`

Wrap related resources in `whenLoaded()` so they only appear when the controller eager-loaded the relationship. This avoids accidental N+1 queries from a serialiser:

```php
return [
    'identifier' => $this->identifier,
    'title' => $this->title,
    'author' => UserResource::make($this->whenLoaded('author')),
    'comments' => CommentResource::collection($this->whenLoaded('comments')),
];
```

## 5. Inline `can` array for authorisation

Expose policy decisions inline so the consumer (frontend, API client) can decide what UI affordances to show without re-checking against the API:

```php
return [
    'identifier' => $this->identifier,
    'title' => $this->title,
    'can' => [
        'update' => logged_in_user()->can('update', $this->resource),
        'delete' => logged_in_user()->can('delete', $this->resource),
    ],
];
```

- Use `logged_in_user()` (helper defined in `laravel-architecture`), not `auth()->user()` directly — it returns a typed `User` and aborts when no user is authenticated, so PHPStan stays happy and the resource never silently leaks a `null` permission.
- `$this->resource` resolves to the underlying model regardless of whether the resource was constructed from a single model or a collection item.
