# Global helpers — adoption setup

One-time setup when adopting the helpers from `laravel-architecture` in a fresh project. Load this only when first wiring up the helpers in a new codebase.

## Files to copy

- Copy `assets/helpers.php` to `helpers/general.php` (or any name) and add the path to `composer.json`'s `"autoload": { "files": [...] }`. Run `composer dump-autoload`.
- Copy `assets/Rules/GivenValueShouldExists.php` to `app/Rules/GivenValueShouldExists.php`.

## Required `User->timezone()` method

The `user_timezone()` helper calls a `timezone()` method on the User model. If it's missing, add one of these patterns:

**Pattern A — static fallback** (simplest, no DB changes):

```php
// app/Models/User.php
public function timezone(): string
{
    return 'Europe/Brussels';
}
```

**Pattern B — per-user attribute** (each user picks their own timezone):

```php
// migration: $table->string('timezone')->nullable();

public function timezone(): string
{
    return $this->timezone ?? 'Europe/Brussels';
}
```

Pick A when you don't yet need user-level timezone control — start simple, swap later. Pick B when users span multiple timezones and the difference matters for display. Adapt the fallback timezone to your project's locale.
