<?php

namespace InnoGE\LaravelEnumStates;

use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;

final class StateMachine
{
    /** @var array<string, array<string, array<int, callable|class-string>>> */
    private array $transitions = [];

    /** @var array<string, array<int, callable|class-string>> */
    private array $enteringActions = [];

    /** @var array<string, array<int, callable|class-string>> */
    private array $leavingActions = [];

    private ?StateEnum $default = null;

    /**
     * @param  class-string<StateEnum>  $enum
     */
    public function __construct(private readonly string $enum) {}

    public function default(StateEnum $state): self
    {
        $this->ensureEnum($state);

        $this->default = $state;

        return $this;
    }

    /**
     * @param  StateEnum|array<int, StateEnum>  $from
     * @param  StateEnum|array<int, StateEnum>  $to
     * @param  array<int, callable|class-string>  $actions
     */
    public function allow(StateEnum|array $from, StateEnum|array $to, array $actions = []): self
    {
        foreach ($this->states($from) as $fromState) {
            $fromKey = $this->key($fromState);

            foreach ($this->states($to) as $toState) {
                $toKey = $this->key($toState);

                $this->transitions[$fromKey][$toKey] = [
                    ...($this->transitions[$fromKey][$toKey] ?? []),
                    ...$actions,
                ];
            }
        }

        return $this;
    }

    /**
     * @param  StateEnum|array<int, StateEnum>  $state
     * @param  array<int, callable|class-string>  $actions
     */
    public function onEntering(StateEnum|array $state, array $actions): self
    {
        foreach ($this->states($state) as $case) {
            $this->enteringActions[$this->key($case)] = [
                ...($this->enteringActions[$this->key($case)] ?? []),
                ...$actions,
            ];
        }

        return $this;
    }

    /**
     * @param  StateEnum|array<int, StateEnum>  $state
     * @param  array<int, callable|class-string>  $actions
     */
    public function onLeaving(StateEnum|array $state, array $actions): self
    {
        foreach ($this->states($state) as $case) {
            $this->leavingActions[$this->key($case)] = [
                ...($this->leavingActions[$this->key($case)] ?? []),
                ...$actions,
            ];
        }

        return $this;
    }

    public function defaultState(): ?StateEnum
    {
        return $this->default;
    }

    public function canTransition(StateEnum $from, StateEnum $to): bool
    {
        $this->ensureEnum($from);
        $this->ensureEnum($to);

        return array_key_exists($this->key($to), $this->transitions[$this->key($from)] ?? []);
    }

    /**
     * @return array<int, StateEnum>
     */
    public function transitionableStates(StateEnum $from): array
    {
        $this->ensureEnum($from);

        $allowed = array_keys($this->transitions[$this->key($from)] ?? []);
        $enum = $this->enum;

        return array_values(array_filter(
            $enum::cases(),
            fn (StateEnum $state): bool => in_array($this->key($state), $allowed, true),
        ));
    }

    /**
     * @return array<int, callable|class-string>
     */
    public function actionsFor(StateEnum $from, StateEnum $to): array
    {
        $this->ensureEnum($from);
        $this->ensureEnum($to);

        return [
            ...($this->leavingActions[$this->key($from)] ?? []),
            ...($this->enteringActions[$this->key($to)] ?? []),
            ...($this->transitions[$this->key($from)][$this->key($to)] ?? []),
        ];
    }

    private function ensureEnum(StateEnum $state): void
    {
        InvalidStateMachineConfiguration::ensureStateBelongsToEnum($this->enum, $state);
    }

    private function key(StateEnum $state): string
    {
        return $state->name;
    }

    /**
     * @param  StateEnum|array<int, StateEnum>  $states
     * @return array<int, StateEnum>
     */
    private function states(StateEnum|array $states): array
    {
        $states = is_array($states) ? $states : [$states];

        foreach ($states as $state) {
            $this->ensureEnum($state);
        }

        return $states;
    }
}
