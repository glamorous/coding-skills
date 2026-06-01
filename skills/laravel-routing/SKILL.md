---
name: laravel-routing
description: Use when defining Laravel routes or generating URLs in controllers, Blade views, Livewire components, or tests.
---

# Laravel routing

## 1. Route definition syntax

Use the invokable controller form (no second array element, no `@method`):

```php
Route::get('articles', ListArticlesController::class)
    ->can('viewAny', Article::class);
```

**Never call `->name()` on your own routes.** Naming is reserved for Fortify/framework routes that you don't own.

## 2. URL paths and parameters

- URL paths use kebab-case: `/error-occurrences`, `/open-source`, `/my-account`. Never camelCase or snake_case in the URL.
- Model-binding parameters take the singular model name (`{article}`, `{comment}`) — they resolve via `HasIdentifier` (see §4).
- Non-model parameters use camelCase: `{userId}`, `{invoiceNumber}`. Reserve these for the rare case where you can't use implicit binding.

```php
Route::get('error-occurrences', ListErrorOccurrencesController::class);
Route::get('articles/{article}', EditArticleController::class);
Route::get('reports/{reportType}', ShowReportController::class);
```

## 3. URL generation — `action()` vs `route()`

For your own invokable controllers, generate URLs via `action(Controller::class)`:

```php
redirect()->action(ListArticlesController::class);
```

Reserve `route('login')`, `route('dashboard')`, etc. for Fortify/framework routes that have no controller of your own.

In Blade templates use the fully-qualified class name:

```blade
<a href="{{ action(\App\Http\Controllers\Tickets\ListTicketsController::class) }}">Tickets</a>
```

In PHP (controllers, tests, Livewire components) import the controller and use the short name:

```php
return redirect()->action(ListTicketsController::class);
```

## 4. Implicit model binding

Models that use `HasIdentifier` (see `laravel-models`) automatically resolve route parameters via the ULID `identifier` column. Define the parameter as the model:

```php
Route::get('articles/{article}', EditArticleController::class);
```

For nested resources, use `scopeBindings()` so child parameters are scoped to the parent:

```php
Route::get('articles/{article}/comments/{comment}', ShowCommentController::class)
    ->scopeBindings();
```

## 5. Active-page detection

Detect the active page from the URL path, not from named routes:

```php
// Good
request()->is('tickets*')

// Bad
request()->routeIs('tickets.*')
```

Reason: own routes are unnamed, so `routeIs()` won't match them.

## 6. API routing

The kebab-case rule from §2 also applies to API routes. On top of that:

- Use plural resource names: `/errors`, `/users`, `/articles`.
- Keep nesting shallow — at most one level. Prefer `/articles/{article}/comments` and `/comments/{comment}` over `/articles/{article}/comments/{comment}`. The deeper variant only earns its place when the child genuinely needs the parent context (e.g. for scoping or authorisation), and then only with `scopeBindings()` (see §4).

```
Good:  /error-occurrences
       /articles/{article}/comments
       /comments/{comment}

Bad:   /errorOccurrence
       /article
       /articles/{article}/comments/{comment}/reactions/{reaction}
```
