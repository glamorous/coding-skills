---
name: code-quality-grumphp
description: Use only when the project already has GrumPHP configured (a `grumphp.yml` at the project root or `phpro/grumphp` in `composer.json`), or when the user explicitly asks to set up the GrumPHP quality gate. Do not apply to projects that have not opted in. Covers PHPStan level 8, Pint, complexity limits, magic-number ban, dependency audit, the full test suite, and commit-message rules. Meta-rule — propose fixes to the user when a check fails, never auto-fix or bypass with --no-verify.
---

# Code quality — GrumPHP gate

## 1. What runs and when

GrumPHP wires Git hooks that run automatically on `git commit`. Two hook stages:

### `pre-commit` — runs against staged PHP/JSON files and fails the commit on any violation

- `jsonlint` — JSON files parse, no duplicate keys.
- `laravel_pint` — Laravel Pint auto-formats and re-stages.
- `git_blacklist` — blocks `dd(`, `dump(`, `var_dump(`, `print_r(`, `die(`, `ray(`.
- `phpstan` — PHPStan level 8 (with Larastan).
- `phpcs` — `phpcs.xml` rules (cyclomatic complexity ≤ 10, nesting depth ≤ 5).
- `phpmnd` — magic-number detector across `app/`.
- `securitychecker_composeraudit` — `composer audit` for dependency CVEs.
- `pest` — full test suite.

### `commit-msg` — validates the message itself

- non-empty, capitalised subject, no trailing period, single-line subject.
- max subject width 72.
- matcher `/^.{10,}/` (≥ 10 characters).

## 2. Code requirements that follow from the gate

- No `dd()`, `dump()`, `var_dump()`, `print_r()`, `die()`, `ray()` in non-test code.
- PHPStan level 8 must pass (with Larastan extension for Laravel-aware rules).
- Pint must pass (Laravel preset + project's strict overrides).
- No magic numbers — extract into named constants (see `php-style`).
- Cyclomatic complexity ≤ 10, nesting depth ≤ 5 — refactor early-returns or extract helpers when approaching the limit.
- `declare(strict_types=1);` in every PHP file (also in `php-style`).
- Strict comparison `===` / `!==` everywhere (also in `php-style`).
- Tests pass — Pest runs as part of the gate.

## 3. Manual invocation

```
./vendor/bin/grumphp run                                   # full gate
./vendor/bin/phpstan analyse --memory-limit=-1             # PHPStan only
./vendor/bin/pint                                          # Pint only (auto-fixes)
```

## 4. Meta-rule when the gate fails (important for AI assistants)

When a commit fails on a GrumPHP check, the response is to **propose the fix to the user, not silently apply it.** Show the failing check's output, explain the cause, and let the user decide how to resolve it.

Specifically:

- **Never** add `--no-verify` to bypass the hook.
- **Never** auto-`git add` and re-commit without explicit user approval.
- **Never** skip individual hooks without a clear reason and the user's go-ahead.

Pint's auto-fix is the one exception — it runs as part of the gate itself and re-stages its formatting fixes. That is the gate doing its job.

## 5. Setting up in a fresh project

### Composer dev dependencies

```
composer require --dev \
    phpro/grumphp \
    laravel/pint \
    yieldstudio/grumphp-laravel-pint \
    larastan/larastan \
    phpstan/phpstan \
    squizlabs/php_codesniffer \
    povils/phpmnd \
    pestphp/pest \
    barryvdh/laravel-ide-helper \
    barryvdh/laravel-debugbar
```

`barryvdh/laravel-ide-helper` is required by the gate, not optional: `phpstan.neon` reads `_ide_helper_models.php` via `scanFiles`, and without it level 8 fails on Eloquent query types. `barryvdh/laravel-debugbar` registers itself through Laravel package auto-discovery — no further configuration needed.

### Files to copy

Copy each file from `assets/` to the project root:

- `assets/grumphp.yml` → `grumphp.yml`
- `assets/phpstan.neon` → `phpstan.neon`
- `assets/phpcs.xml` → `phpcs.xml`
- `assets/pint.json` → `pint.json`

Then install the Git hooks:

```
./vendor/bin/grumphp git:init
```

### ide-helper auto-regeneration

Wire the three ide-helper commands into `composer.json` so they run after every `composer install` / `composer update`:

```json
{
    "scripts": {
        "post-update-cmd": [
            "@php artisan ide-helper:generate --ansi",
            "@php artisan ide-helper:meta --ansi",
            "@php artisan ide-helper:models --write-mixin --reset --ansi"
        ]
    }
}
```

These regenerate `_ide_helper.php` (facade signatures), `.phpstorm.meta.php` (PhpStorm container hints), and `_ide_helper_models.php` (model `@mixin` blocks consumed by PHPStan). The third command reads the live database schema, so migrations must have run — drop it from the production CI pipeline if `composer install` runs there before any DB is available.

### Adapting the bundled configs

The bundled configs reflect a particular project's preferences. Adjust per project:

- **`phpstan.neon`** — `paths` and `scanFiles` may differ depending on which folders your project wants analysed. The Larastan and Carbon extensions, level 8, and the Blade/Flux ignores are the parts worth keeping verbatim. The `_ide_helper_models.php` entry under `scanFiles` is produced by the ide-helper composer hook above — keep them aligned if you rename the file.
- **`phpcs.xml`** — change the `<ruleset name>` and `<description>` to whatever fits. The cyclomatic complexity (10/20) and nesting level (5/10) rules are the load-bearing parts.
- **`pint.json`** — Laravel preset plus a strict ruleset. Omit individual rules you don't want; add project-specific ones.
