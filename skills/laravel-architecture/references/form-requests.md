# Form Requests — full rules

**Load this when:** writing or modifying any part of a Form Request class — `rules()`, `authorize()`, `after()`, validation arrays, or getter methods. Also load when adding a new field that needs a typed accessor (Model, Enum, Carbon, Collection), or when reviewing how a controller pulls data out of a request.

This file is the single source of truth for Form Requests.

## Rules at a glance

- One Request class per controller. `authorize()` only when there is authorization logic.
- Validation rules in array format, one rule per line.
- Identifier fields (ULID references to a model): combine `'ulid'` with `new GivenValueShouldExists(fn () => $this->getX())`. Never `'exists:table,identifier'`.
- Date fields: always `'date:Y-m-d'`, never plain `'date'`.
- Controllers always go through custom getters — never directly into the request bag.
- Wrap getters that perform DB queries or call other getters in `once()`.
- Getters return typed objects (Model, Enum, Carbon, Collection) — never raw scalars.
- Use `$this->input()`, `$this->enum()`, `$this->date()`, `$this->integer()`, `$this->string()` — not bracket access or `$this->get()`.

The sections below expand each rule with the required PHPDoc shapes, full templates, and examples.

## Structure

- One Request class per controller.
- Precognition is supported.
- Add `authorize()` only when there is authorization logic.
- Validation rules are always in array format, one rule per line.
- PHPDoc on `rules()`:

  ```php
  /** @return array<string, array<int, mixed>> */
  public function rules(): array
  ```

- Use `Rule::enum()`, `Rule::unique()`, `Rule::date()`, or closures for complex validation.

## Identifier fields

ULID references to a model: always combine `'ulid'` with `GivenValueShouldExists(fn () => $this->getX())`. Never use `'exists:table,identifier'` — the custom rule reuses the getter and avoids duplicate queries.

```php
'project_identifier' => [
    'required',
    'ulid',
    new GivenValueShouldExists(fn () => $this->getProject()),
],
```

## Date fields

Always pass an explicit format (`'date:Y-m-d'`), never plain `'date'`.

```php
'requested_delivery_date' => [
    'nullable',
    'date:Y-m-d',
    'after:today',
],
```

## Post-validation checks — `after()`

```php
/** @return array<int, callable(Validator): void> */
public function after(): array
```

## Getter methods & `once()`

Custom getters extract data from the request. Controllers always go through getters — never directly into the request bag.

Wrap getters that perform DB queries or call other getters in `once()` to prevent duplicate work.

Principles:

- Getters return typed objects (Model, Enum, Carbon, Collection) — never raw strings/integers.
- Use `$this->input()`, `$this->enum()`, `$this->date()`, `$this->integer()`, `$this->string()` — not bracket access or the deprecated `$this->get()`.
- Plain scalars without transformation may be read directly: `$request->string('name')`, `$request->float('amount')`, etc.

### Required getter (throws when missing)

```php
public function getCategory(): Category
{
    return once(function () {
        $identifier = $this->input('category');
        throw_unless($identifier, ModelNotFoundException::class);

        return Category::query()
            ->where('identifier', '=', $identifier)
            ->firstOrFail();
    });
}
```

### Nullable getter (returns `null` when blank)

```php
public function getCategory(): ?Category
{
    return once(function () {
        $identifier = $this->input('category');

        if (is_null($identifier)) {
            return null;
        }

        return Category::query()
            ->where('identifier', '=', $identifier)
            ->first();
    });
}
```

### Enum getter (uses `throw_unless()`)

```php
public function getStatus(): AbsenceStatus
{
    $status = $this->enum('status', AbsenceStatus::class);
    throw_unless($status, new NotFoundHttpException('Status not found.'));

    return $status;
}
```
