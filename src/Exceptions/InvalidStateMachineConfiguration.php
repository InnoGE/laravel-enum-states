<?php

namespace InnoGE\LaravelEnumStates\Exceptions;

use InnoGE\LaravelEnumStates\Contracts\StateEnum;

final class InvalidStateMachineConfiguration extends EnumStatesException
{
    public static function enumMustImplementStateEnum(string $enum): self
    {
        return new self("State machine enum [{$enum}] must implement [".StateEnum::class.'].');
    }

    public static function wrongEnum(string $expected, StateEnum $state): self
    {
        return new self('State ['.$state::class."] does not belong to state machine enum [{$expected}].");
    }

    /**
     * @param  class-string<StateEnum>  $expected
     */
    public static function ensureStateBelongsToEnum(string $expected, StateEnum $state): void
    {
        if (! $state instanceof $expected) {
            throw self::wrongEnum($expected, $state);
        }
    }

    public static function unresolvableAction(string $action): self
    {
        return new self("Transition action [{$action}] must be a closure or an invokable class.");
    }

    public static function duplicateField(string $model, string $field): self
    {
        return new self("Model [{$model}] registers enum state field [{$field}] more than once.");
    }

    public static function missingEnumCast(string $model, string $field, string $enum): self
    {
        return new self("Model [{$model}] must cast enum state field [{$field}] to [{$enum}].");
    }

    public static function mismatchedEnumCast(string $model, string $field, string $expected, string $actual): self
    {
        return new self("Model [{$model}] casts enum state field [{$field}] to [{$actual}], expected [{$expected}].");
    }
}
