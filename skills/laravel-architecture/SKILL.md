---
name: laravel-architecture
description: Use when creating or modifying Laravel controllers, Form Requests, Action classes, configuration files, Artisan commands, Jobs, Events, Listeners, Mailables, or global helpers used in the request lifecycle.
---

# Laravel architecture

**Follow Laravel conventions first.** When the framework documents an idiomatic way to do something, use it — only deviate when there is a clear reason. The rules below either reinforce a Laravel convention or document a project-level layer that sits on top of it.

This skill covers the request-lifecycle layer: how a request flows from controller through Form Request to Action, plus the global helper functions used along the way. It also documents project-wide conventions for configuration, Artisan commands, and naming of framework-managed classes.

## 1. Controllers — invokable single-action

Each controller has exactly one `__invoke()` method. No resource controllers.

Directory layout per resource:

```
Controllers/Articles/
├── CreateArticleController.php
├── EditArticleController.php
├── ListArticlesController.php
├── StoreArticleController.php
└── UpdateArticleController.php
```

Controllers stay thin: they coordinate between Request and Action/Model and contain no business logic. They fetch data via Form Request getters and pass typed objects (Models, Enums, DTOs) to Actions or models.

Example controller body:

```php
public function __invoke(StoreArticleRequest $request): RedirectResponse
{
    $article = new Article;
    $article->category_id = $request->getCategory()?->id;
    $article->unit = $request->getUnit();
    $article->save();

    return redirect()->action(ListArticlesController::class);
}
```

## 2. Actions

Business logic lives in Action classes with an `execute()` method (not invokable). Dependencies are constructor-injected via PHP 8 promoted properties. Actions receive complete, validated objects (Models, Enums, DTOs) — never raw scalar input.

```php
class CreateRegistrationAction
{
    public function __construct(
        private Company $company,
        private User $user,
    ) {}

    public function execute(): void
    {
        // ...
    }
}
```

## 3. Form Requests

> **Before editing any Form Request — `rules()`, `authorize()`, `after()`, validation arrays, or getter methods — load `references/form-requests.md`. That file is the single source of truth: rules summary, identifier/date validation patterns, required PHPDoc shapes, and getter templates (required / nullable / enum) with `once()` and `throw_unless()`.**

## 4. Global helpers

Two groups of helper functions are autoloaded via composer's `"autoload": { "files": [...] }` and used across the codebase.

### Auth / error helpers

- `user(): ?User` — typed wrapper around `auth()->user()`. Use when the caller might not be authenticated.
- `logged_in_user(): User` — returns the current user or aborts `401`. Use in policy checks, Resources' `can` arrays, anywhere the action requires an authenticated user.
- `report_or_throw(Throwable $exception): void` — throws in local environments (so the dev sees the failure), reports to the error tracker in non-local. Use in `try`/`catch` blocks where the failure should not break user flow.

### Display helpers (uniform date presentation)

- `format_datetime(CarbonInterface $date): string` — timezone-aware display as `d/m/Y H:i`. For timestamps in the UI.
- `format_date(CarbonInterface $date): string` — display as `d/m/Y`, no timezone conversion. For date-only fields.

There are three contexts where dates appear, each with its own rule:

| Context | Format | Approach |
|---|---|---|
| User-facing display (Blade, Livewire, mails) | `d/m/Y` / `d/m/Y H:i`, user timezone | `format_date()` / `format_datetime()` |
| HTML form inputs (`<input type="date">`) | ISO `Y-m-d` | inline `->format('Y-m-d')` (helpers do not apply) |
| API responses / webhooks / JSON | ATOM / RFC 3339 | `->toAtomString()` (Carbon built-in) |

Never call `->format('d/m/Y')` or `->format('d/m/Y H:i')` directly in views — always use the helpers. This is what gives the project a single source of truth for display formatting.

**Supporting helper:** `user_timezone(): string` — returns the current user's timezone or a hard-coded locale fallback when no user is authenticated.

### Helpers reference

| Item | Purpose |
|---|---|
| `user(): ?User` | Typed `auth()->user()` accessor (satisfies PHPStan level 8). |
| `logged_in_user(): User` | Returns the authenticated User or aborts `401`. Memoised per request. |
| `report_or_throw(Throwable): void` | Throws locally, reports otherwise. |
| `user_timezone(): string` | Current user's timezone with a locale fallback. |
| `format_datetime(CarbonInterface): string` | Timezone-aware uniform timestamp display. |
| `format_date(CarbonInterface): string` | Uniform date display, no timezone conversion. |
| `GivenValueShouldExists` (rule) | Validation rule that defers existence to a closure. |

→ **Load `references/helpers-setup.md`** only when adopting these helpers in a fresh project for the first time — it covers the files to copy, the composer autoload wiring, and the `User->timezone()` method patterns. Skip during normal day-to-day editing.

## 5. Configuration

- **Use `config()` outside config files, never `env()`.** The framework caches config; `env()` calls outside of `config/*.php` return `null` once `config:cache` runs. Read environment values inside config files only:

  ```php
  // config/services.php
  return [
      'github' => [
          'token' => env('GITHUB_TOKEN'),
      ],
  ];

  // anywhere else
  $token = config('services.github.token');
  ```

- Config file names use kebab-case: `pdf-generator.php`, `error-tracking.php`.
- Config keys use snake_case: `chrome_path`, `default_timezone`.
- Add third-party service configuration to `config/services.php` rather than creating a new file.

## 6. Artisan commands

- Command names use kebab-case: `php artisan delete-old-records`, `php artisan rebuild-search-index`.
- Always provide feedback so callers (humans, schedulers, CI logs) can see what happened:

  ```php
  $this->info('Starting cleanup...');
  // ...
  $this->comment('All ok!');
  ```

- For loops, log each item *before* processing it (so the last visible line points at the failing item when something throws), then end with a summary:

  ```php
  $items->each(function (Item $item): void {
      $this->info("Processing item id `{$item->id}`...");
      $this->processItem($item);
  });

  $this->comment("Processed {$items->count()} items.");
  ```

## 7. Naming for framework classes

Convention per file type. PascalCase always; the suffix and verb form depend on the type:

| Type | Pattern | Examples |
|---|---|---|
| Jobs | action verb + object, no suffix | `CreateUser`, `SendEmailNotification`, `RebuildSearchIndex` |
| Events | tense-based, no suffix | `UserRegistering` (before), `UserRegistered` (after) |
| Listeners | action + `Listener` suffix | `SendInvitationMailListener`, `LogUserRegisteredListener` |
| Console commands | action + `Command` suffix | `PublishScheduledPostsCommand`, `RebuildSearchIndexCommand` |
| Mailables | purpose + `Mail` suffix | `AccountActivatedMail`, `InvoiceReadyMail` |
