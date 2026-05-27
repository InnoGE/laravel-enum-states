<?php

namespace InnoGE\LaravelEnumStates;

use BackedEnum;
use Illuminate\Contracts\Support\Htmlable;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;

final class StateOptions
{
    /**
     * @param  class-string<StateEnum>  $enum
     * @return array<array-key, string>
     */
    public static function all(string $enum): array
    {
        return self::mapCases($enum, $enum::cases());
    }

    /**
     * @param  class-string<StateEnum>  $enum
     * @return array<array-key, string>
     */
    public static function enabled(string $enum, StateEnum|string|int|null $current): array
    {
        $current = self::currentState($enum, $current);

        if ($current === null) {
            return [];
        }

        return self::mapCases($enum, [
            $current,
            ...StateMachineRegistry::forEnum($enum)->transitionableStates($current),
        ]);
    }

    /**
     * @param  class-string<StateEnum>  $enum
     * @return array<int, array-key>
     */
    public static function disabledValues(string $enum, StateEnum|string|int|null $current): array
    {
        return array_values(array_diff(
            array_keys(self::all($enum)),
            array_keys(self::enabled($enum, $current)),
        ));
    }

    /**
     * @param  class-string<StateEnum>  $enum
     */
    public static function coerce(string $enum, mixed $value): ?StateEnum
    {
        if ($value instanceof $enum) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        if (is_subclass_of($enum, BackedEnum::class)) {
            if (! is_string($value) && ! is_int($value)) {
                return null;
            }

            /** @var StateEnum|null */
            return $enum::tryFrom($value);
        }

        foreach ($enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    public static function value(StateEnum $state): int|string
    {
        return $state instanceof BackedEnum ? $state->value : $state->name;
    }

    /**
     * @param  class-string<StateEnum>  $enum
     */
    public static function currentState(string $enum, StateEnum|string|int|null $state): ?StateEnum
    {
        $state = $state === null
            ? StateMachineRegistry::forEnum($enum)->defaultState()
            : self::coerce($enum, $state);

        if ($state !== null) {
            InvalidStateMachineConfiguration::ensureStateBelongsToEnum($enum, $state);
        }

        return $state;
    }

    /**
     * @param  class-string<StateEnum>  $enum
     * @param  array<int, StateEnum>  $states
     * @return array<array-key, string>
     */
    private static function mapCases(string $enum, array $states): array
    {
        $allowedValues = [];

        foreach ($states as $state) {
            InvalidStateMachineConfiguration::ensureStateBelongsToEnum($enum, $state);

            $allowedValues[self::value($state)] = true;
        }

        $options = [];

        foreach ($enum::cases() as $case) {
            if (! array_key_exists(self::value($case), $allowedValues)) {
                continue;
            }

            $options[self::value($case)] = self::label($case);
        }

        return $options;
    }

    private static function label(StateEnum $state): string
    {
        if (! method_exists($state, 'getLabel')) {
            return $state->name;
        }

        $label = $state->getLabel();

        if ($label instanceof Htmlable) {
            return $label->toHtml();
        }

        return is_string($label) ? $label : $state->name;
    }
}
