<?php

namespace InnoGE\LaravelEnumStates\Concerns;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;
use InnoGE\LaravelEnumStates\Support\StateMachineHandler;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;

trait TransitionsState
{
    public function transitionTo(Model $model, string $field, StateEnum $state, mixed ...$context): void
    {
        $registration = StateMachineRegistry::forField($model, $field);

        InvalidStateMachineConfiguration::ensureStateBelongsToEnum($registration->enum, $this);
        InvalidStateMachineConfiguration::ensureStateBelongsToEnum($registration->enum, $state);

        $namedContext = [];

        foreach ($context as $key => $value) {
            $namedContext[(string) $key] = $value;
        }

        $model->setAttribute($field, $state);
        app(StateMachineHandler::class)->putContext($model, $field, $namedContext);
    }

    public function canTransitionTo(StateEnum $state): bool
    {
        return StateMachineRegistry::forEnum($this::class)->canTransition($this, $state);
    }

    /**
     * @return array<int, StateEnum>
     */
    public function transitionableStates(): array
    {
        return StateMachineRegistry::forEnum($this::class)->transitionableStates($this);
    }

    public static function canTransition(StateEnum $from, StateEnum $to): bool
    {
        return StateMachineRegistry::forEnum(static::class)->canTransition($from, $to);
    }

    /**
     * @return array<int, StateEnum>
     */
    public static function transitionableStatesFrom(StateEnum $from): array
    {
        return StateMachineRegistry::forEnum(static::class)->transitionableStates($from);
    }
}
