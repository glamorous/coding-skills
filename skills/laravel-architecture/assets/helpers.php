<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\CarbonInterface;

if (! function_exists('user')) {
    function user(): ?User
    {
        /** @var ?User */
        return auth()->user();
    }
}

if (! function_exists('logged_in_user')) {
    function logged_in_user(): User
    {
        return once(function () {
            $user = user();

            if ($user instanceof User) {
                return $user;
            }

            abort(401, 'User should be logged in.');
        });
    }
}

if (! function_exists('report_or_throw')) {
    function report_or_throw(Throwable $exception): void
    {
        if (app()->isLocal()) {
            throw $exception;
        }

        report($exception);
    }
}

if (! function_exists('user_timezone')) {
    function user_timezone(): string
    {
        $user = auth()->user();

        if (is_null($user)) {
            // Adjust to your project's locale.
            return 'Europe/Brussels';
        }

        return $user->timezone();
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime(CarbonInterface $date): string
    {
        return $date->copy()->setTimezone(user_timezone())->format('d/m/Y H:i');
    }
}

if (! function_exists('format_date')) {
    function format_date(CarbonInterface $date): string
    {
        return $date->format('d/m/Y');
    }
}
