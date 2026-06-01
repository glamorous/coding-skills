---
name: laravel-data-objects-enums
description: Use when creating or modifying PHP enum types or Data Object (DTO) classes.
---

# Data Objects & Enums

## 1. Enums

- Always use **backed enums** (`int` or `string`).
- Use TitleCase for case names (`OnHold`, `BestLake`, not `ON_HOLD`).
- Add a `label()` method for human-readable / translated labels:

  ```php
  enum Role: int
  {
      case User = 1;
      case Admin = 2;
      case Oversight = 3;

      public function label(): string
      {
          return match ($this) {
              self::User => __('users.roles.user'),
              self::Admin => __('users.roles.admin'),
              self::Oversight => __('users.roles.oversight'),
          };
      }
  }
  ```

- In `match` expressions, place each case sharing the same arm value on its own line:

  ```php
  return match ($this) {
      self::OnHold,
      self::Closed,
      self::Duplicate => 'zinc',
      self::Cancelled,
      self::Rejected => 'red',
  };
  ```

## 2. Data Objects (DTOs)

- DTOs are `readonly` classes with constructor property promotion. They are immutable and JSON-serialisable.
- Provide static factory methods (`fromX(...)`, `fromArray(...)`) when construction needs derivation.

```php
readonly class StatusTransition
{
    public function __construct(
        public TicketStatus $target,
        public string $label,
        public ReasonRequirement $reason = ReasonRequirement::None,
        public bool $requiresAssignee = false,
        public bool $clearsAssignee = false,
    ) {}
}
```

- When a DTO needs to be stored on a model attribute, implement Laravel's `Castable` interface and register it via the model's `casts()` method (see `laravel-models`).

## 3. Casting in models (cross-reference)

Enums and DTOs are wired into Eloquent through the `casts()` method on the model:

```php
protected function casts(): array
{
    return [
        'role' => Role::class,
        'status_transition' => StatusTransition::class,
    ];
}
```
