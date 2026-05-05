---
name: php-style
description: Use when writing or reviewing PHP code in any file. Enforces strict types, strict comparison, type hints and return types (including short nullable `?Type`), curly braces, PHP 8 constructor property promotion, use statements over fully-qualified names (also in PHPDoc), shorthand array shape syntax, grouped constants, camelCase for non-public-facing names, no docblocks on fully-typed methods, no magic numbers, array-element-per-line formatting, control-flow rules (happy path last, early returns over else, multiple ifs over compound conditions, multi-line ternary), string interpolation over concatenation, and one trait per use-statement.
---

# PHP style

## 1. Strict types and strict comparison

- Every PHP file starts with `declare(strict_types=1);` (after the opening tag, before the namespace).
- Always compare with `===` / `!==`. Never `==` / `!=`.

## 2. Type hints and return types

- Every method declares parameter types and a return type, including `void`:

  ```php
  function isAccessible(User $user, ?string $path = null): bool
  ```

- Use the short nullable notation `?Type` instead of `Type|null`:

  ```php
  // Good
  public function getCategory(): ?Category

  // Bad
  public function getCategory(): Category|null
  ```

- Property types live in PHP syntax, not in `@var` docblocks:

  ```php
  // Good
  private GitHub $github;

  // Bad
  /** @var GitHub */
  private $github;
  ```

- Use PHP 8 constructor property promotion. Don't leave empty zero-parameter `__construct()` methods unless the constructor is private:

  ```php
  public function __construct(
      private readonly GitHub $github,
  ) {}
  ```

## 3. Curly braces always

Use curly braces for control structures, even single-line bodies:

```php
if ($user->isAdmin()) {
    return true;
}
```

## 4. `use` statements over fully-qualified names

Import every class. Use the short name in **return types, parameter types, AND PHPDoc annotations.** Never inline an FQN.

```php
// Good
use Illuminate\Support\Collection;
use App\Models\User;

/** @var User $assignee */
private function getRecipients(): Collection { /* ... */ }

// Bad
/** @var \App\Models\User $assignee */
private function getRecipients(): \Illuminate\Support\Collection { /* ... */ }
```

## 5. PHPDoc array shapes

Use shorthand array syntax (`StatusTransition[]`) over generic notation (`list<StatusTransition>`, `array<int, StatusTransition>`):

```php
/** @return StatusTransition[] */
public function transitions(): array
```

**Exception — `Collection`**: array-shorthand isn't valid on `Collection`, so use generics there:

```php
/** @return Collection<int, User> */
public function getUsers(): Collection
```

For associative shapes use object-shape notation. With multiple keys, place each key on its own line:

```php
// Single key — inline is fine
/** @return array{name: string} */

// Multiple keys — one per line
/** @return array{
 *     first: SomeClass,
 *     second: SomeClass,
 * } */
```

## 6. Constants grouping

Constants with the same visibility sit directly under each other — no blank line between them. Group by visibility:

```php
// Good
private const string BUSINESS_TIMEZONE = 'Europe/Brussels';
private const int BUSINESS_START_HOUR = 8;
private const int ROUNDING_INTERVAL_MINUTES = 15;

// Bad
private const string BUSINESS_TIMEZONE = 'Europe/Brussels';

private const int BUSINESS_START_HOUR = 8;

private const int ROUNDING_INTERVAL_MINUTES = 15;
```

## 7. Naming

- Use descriptive names for variables and methods: `isRegisteredForDiscounts`, not `discount()`.
- Use camelCase for non-public-facing identifiers — variables, methods, and internal array keys (config keys are the exception, see `laravel-architecture`).
- Use TitleCase for Enum case names (covered in `laravel-data-objects-enums`).

## 8. Comments

- Prefer PHPDoc blocks over inline comments. Add inline comments only for non-obvious logic that can't be captured by good naming.
- Don't restate what well-named code already shows.
- Skip the docblock entirely on methods that are fully type-hinted (parameters + return type) unless a description adds genuine information. A docblock that only repeats the signature is noise.

## 9. No magic numbers

Extract non-trivial numeric literals into named constants:

```php
// Good
private const int MAX_RETRIES = 3;
// ...
if ($attempts >= self::MAX_RETRIES) { /* ... */ }

// Bad
if ($attempts >= 3) { /* ... */ }
```

Trivial cases (`0`, `1`, array indices, loop counters) are fine inline.

## 10. Array formatting

Array elements always go on separate lines, even when only 1–2 elements:

```php
// Good
->with([
    'assignee',
    'creator',
])

return view('tickets.index', [
    'tickets' => $tickets,
    'filter' => $filter,
]);
```

**Exception:** short middleware arrays may stay inline:

```php
->middleware(['auth', 'verified'])
```

## 11. Control flow

### Happy path last

Handle the failure / guard cases first, leave the success path at the bottom:

```php
// Good
if (! $user) {
    return null;
}

if (! $user->isActive()) {
    return null;
}

return $user->profile();
```

### Avoid `else` — use early returns

A function should not need an `else` branch. If a check fails, return (or throw) immediately:

```php
// Good
if (! $user->isAdmin()) {
    return false;
}

return $user->company->isActive();

// Bad
if ($user->isAdmin()) {
    return $user->company->isActive();
} else {
    return false;
}
```

### Multiple `if`-statements over compound conditions

Splitting failure checks keeps each reason individually traceable (logging, error messages, debugging):

```php
// Good
if (! $user) {
    return null;
}

if (! $user->isActive()) {
    return null;
}

// Bad
if (! $user || ! $user->isActive()) {
    return null;
}
```

### Multi-line ternary

Short ternaries stay inline; longer ones break each part onto its own line:

```php
// Short — inline
$name = $isFoo ? 'foo' : 'bar';

// Longer — each part on its own line
$result = $object instanceof Model
    ? $object->name
    : 'A default value';
```

## 12. String interpolation

Use string interpolation, not concatenation:

```php
// Good
$message = "Hello {$user->name}, you have {$count} new messages.";

// Bad
$message = 'Hello ' . $user->name . ', you have ' . $count . ' new messages.';
```

## 13. Traits

One trait per `use`-statement:

```php
// Good
use HasIdentifier;
use HasBuilder;
use SoftDeletes;

// Bad
use HasIdentifier, HasBuilder, SoftDeletes;
```
