---
name: laravel-models
description: Use when creating or modifying Eloquent models, custom Builders, Filters, migrations, or anywhere a model reference is exposed (Blade, Livewire, routes, resources, requests).
---

# Laravel models

Covers everything that defines or exposes a domain model: the Eloquent model itself, its custom Builder and Filter, its migration, and the rule that its public reference is always the ULID `identifier` — never the numeric `id`.

## 1. Models

- Use the `HasIdentifier` trait so a ULID `identifier` column acts as the route key.
- Use Laravel's `HasBuilder` trait when a model has a custom query builder.
- Add `SoftDeletes` to most models.
- Define a `casts()` method for enum/DTO casting (preferred over the `$casts` property).
- Because `HasIdentifier` overrides `getRouteKeyName()` to return `'identifier'`, route definitions resolve URL parameters to models via the ULID column automatically (the route-side syntax lives in `laravel-routing`).
- After creating or changing a model, run:

  ```
  php artisan ide-helper:models --write-mixin --reset --ansi
  ```

  to refresh `_ide_helper_models.php`.
- **Always start Eloquent queries from `Model::query()`** so chained methods (`->withTrashed()`, `->select()`, `->where()`, …) line up neatly:

  ```php
  // Good
  User::query()
      ->withTrashed()
      ->where('role', '=', Role::Admin)
      ->get();

  // Bad
  User::withTrashed()
      ->where('role', '=', Role::Admin)
      ->get();
  ```

## 2. Custom Eloquent Builders

Each model may have its own Builder under `app/Builder/`. Wire it via `HasBuilder` and the `$builder` property:

```php
class Article extends Model
{
    use HasBuilder;

    protected static string $builder = ArticleBuilder::class;
}
```

Builders expose an `applyFilter(ArticleFilter $filter): self` method and scoped query methods.

## 3. Filters

Filters are `readonly` classes that extend `AbstractFilter`. Constructor properties describe the filter shape:

```php
readonly class ArticleFilter extends AbstractFilter
{
    public function __construct(
        public ?Company $company = null,
        public ?string $searchString = null,
    ) {}
}
```

Filters carry no behaviour — the matching `Builder::applyFilter()` interprets them.

## 4. Policies

Use standard Laravel policies. Reference them in route middleware (`->can('viewAny', Article::class)`) and in API Resources via the inline `can` array (covered in `laravel-resources`).

## 5. Database conventions (migrations)

- ULID public identifier column: `$table->ulid('identifier')->unique();`
- Soft deletes: `$table->softDeletes();`
- Foreign keys with cascade delete.
- JSON columns for metadata.

## 6. The identifier rule — never expose `id` to the outside

A model's `id` is an internal database concern. The only public reference is its `identifier` (the ULID set up by `HasIdentifier`). Treat this as a hard invariant.

**Blade & form inputs** — option values and hidden fields use the identifier:

```blade
<option value="{{ $user->identifier }}">{{ $user->name }}</option>
<input type="hidden" name="article_identifier" value="{{ $article->identifier }}">
```

**Validation rules** — validate ULID format and existence via the request getter:

```php
'project_identifier' => [
    'required',
    'ulid',
    new GivenValueShouldExists(fn () => $this->getProject()),
],
```

Never `'exists:table,identifier'` or `'exists:table,id'`. The `GivenValueShouldExists` rule (see `laravel-architecture`) reuses the getter and avoids a duplicate query.

**Request getters** — look models up by `identifier`, not `id`:

```php
return Project::query()
    ->where('identifier', '=', $identifier)
    ->firstOrFail();
```

**Routes** — implicit binding resolves URL segments via `identifier` automatically (see `laravel-routing`), so generated URLs expose ULIDs.

**API responses** — return `identifier`, not `id`:

```php
return [
    'identifier' => $this->identifier,
    'name' => $this->name,
];
```

Full Resource shape conventions live in `laravel-resources`.

## Files to copy

When adopting in a fresh project:

- Copy `assets/Traits/HasIdentifier.php` to `app/Traits/HasIdentifier.php`.
- Copy `assets/Filters/AbstractFilter.php` to `app/Filters/AbstractFilter.php`.
