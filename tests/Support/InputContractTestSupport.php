<?php

declare(strict_types=1);

namespace Tests\Support;

use ErrorException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Models\Shape\InputContractPost;
use Tests\Models\Shape\InputContractSoftDeletePost;

final class InputContractTestSupport
{
    public static function snapshot(Builder $query): array
    {
        return [$query->toSql(), $query->getBindings()];
    }

    public static function model(string $family): InputContractPost|InputContractSoftDeletePost
    {
        return $family === 'trashed'
            ? new InputContractSoftDeletePost
            : new InputContractPost;
    }

    public static function validationException(callable $callback): ValidationException
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            return $exception;
        }

        throw new RuntimeException('Expected a ValidationException.');
    }

    public static function withPhpWarningsAsExceptions(callable $callback): mixed
    {
        set_error_handler(
            static fn (int $severity, string $message, string $file, int $line) => throw new ErrorException(
                $message,
                0,
                $severity,
                $file,
                $line,
            )
        );

        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}
