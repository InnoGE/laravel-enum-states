<?php

namespace InnoGE\LaravelEnumStates\Filament;

use Closure;
use Filament\Forms\Components\Select;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;
use InnoGE\LaravelEnumStates\StateOptions;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;

final class EnumStateSelect extends Select
{
    /** @var class-string<StateEnum>|Closure|null */
    private string|Closure|null $stateEnum = null;

    private mixed $currentState = null;

    /**
     * @param  class-string<StateEnum>|Closure  $enum
     */
    public function stateEnum(string|Closure $enum): static
    {
        $this->stateEnum = $enum;

        $this->options(fn (): array => StateOptions::all($this->getStateEnum()));

        $this->disableOptionWhen(fn (mixed $value): bool => $this->shouldDisableOption($value));

        return $this;
    }

    public function currentState(StateEnum|string|int|null|Closure $state): static
    {
        $this->currentState = $state;

        return $this;
    }

    /**
     * @return class-string<StateEnum>
     */
    public function getStateEnum(): string
    {
        $enum = $this->evaluate($this->stateEnum);

        if (! is_string($enum) || ! is_subclass_of($enum, StateEnum::class)) {
            throw InvalidStateMachineConfiguration::enumMustImplementStateEnum(is_string($enum) ? $enum : get_debug_type($enum));
        }

        return $enum;
    }

    private function shouldDisableOption(mixed $value): bool
    {
        $enum = $this->getStateEnum();
        $option = StateOptions::coerce($enum, $value);
        $current = $this->resolvedCurrentState($enum);

        if ($option === null || $current === null || $option === $current) {
            return false;
        }

        return ! StateMachineRegistry::forEnum($enum)->canTransition($current, $option);
    }

    /**
     * @param  class-string<StateEnum>  $enum
     */
    private function resolvedCurrentState(string $enum): ?StateEnum
    {
        $state = $this->currentState instanceof Closure
            ? $this->evaluate($this->currentState)
            : $this->currentState;

        $state ??= $this->getState();

        if (! $state instanceof StateEnum && ! is_string($state) && ! is_int($state) && $state !== null) {
            return null;
        }

        return StateOptions::currentState($enum, $state);
    }
}
