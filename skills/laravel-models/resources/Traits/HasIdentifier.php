<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property string $identifier
 *
 * @phpstan-require-extends Model
 */
trait HasIdentifier
{
    use HasUlids {
        HasUlids::newUniqueId as protected laravelNewUniqueId;
    }

    protected static function bootHasIdentifier(): void
    {
        self::saving(static fn (self $model) => $model->identifier = self::getIdentifier($model));
    }

    protected static function getIdentifier(self $model): string
    {
        return $model->identifier ?: (string) Str::ulid();
    }

    public function getRouteKeyName(): string
    {
        return 'identifier';
    }

    /**
     * @return string[]
     */
    public function uniqueIds(): array
    {
        return [
            'identifier',
        ];
    }

    public function newUniqueId(): string
    {
        return strtoupper($this->laravelNewUniqueId());
    }
}
