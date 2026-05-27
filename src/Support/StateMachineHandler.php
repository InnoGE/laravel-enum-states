<?php

namespace InnoGE\LaravelEnumStates\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateTransition;
use WeakMap;

final class StateMachineHandler
{
    /** @var WeakMap<Model, array<string, array<string, mixed>>> */
    private WeakMap $contexts;

    /** @var WeakMap<Model, array<int, StateTransition>> */
    private WeakMap $pending;

    public function __construct(private Container $container)
    {
        $this->contexts = new WeakMap;
        $this->pending = new WeakMap;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function putContext(Model $model, string $field, array $context): void
    {
        $contexts = $this->contexts[$model] ?? [];
        $contexts[$field] = $context;

        $this->contexts[$model] = $contexts;
    }

    public function saving(Model $model): void
    {
        unset($this->pending[$model]);

        try {
            $registrations = StateMachineRegistry::forModel($model);

            $this->applyDefaults($model, $registrations);
            $this->pending[$model] = $this->validateTransitions($model, $registrations);
        } catch (\Throwable $exception) {
            $this->forget($model);

            throw $exception;
        }
    }

    public function saved(Model $model): void
    {
        try {
            $invoker = new ActionInvoker($this->container);

            foreach ($this->pending[$model] ?? [] as $transition) {
                $registration = StateMachineRegistry::forField($model, $transition->field);

                foreach ($registration->machine->actionsFor($transition->from, $transition->to) as $action) {
                    $invoker->invoke($action, $transition);
                }
            }
        } finally {
            $this->forget($model);
        }
    }

    private function forget(Model $model): void
    {
        unset($this->contexts[$model], $this->pending[$model]);
    }

    /**
     * @param  array<string, RegisteredStateMachine>  $registrations
     */
    private function applyDefaults(Model $model, array $registrations): void
    {
        foreach ($registrations as $registration) {
            if ($model->getAttribute($registration->field) !== null) {
                continue;
            }

            if ($registration->machine->defaultState() === null) {
                continue;
            }

            $model->setAttribute($registration->field, $registration->machine->defaultState());
        }
    }

    /**
     * @param  array<string, RegisteredStateMachine>  $registrations
     * @return array<int, StateTransition>
     */
    private function validateTransitions(Model $model, array $registrations): array
    {
        $transitions = [];

        foreach ($registrations as $registration) {
            if (! $model->isDirty($registration->field)) {
                continue;
            }

            $from = $this->originalState($model, $registration) ?? $registration->machine->defaultState();
            $to = $this->currentState($model, $registration);

            if (! $from instanceof StateEnum || ! $to instanceof StateEnum || $from === $to) {
                continue;
            }

            if (! $registration->machine->canTransition($from, $to)) {
                throw InvalidStateTransition::make($model::class, $registration->field, $from, $to);
            }

            $transitions[] = new StateTransition(
                $model,
                $registration->field,
                $from,
                $to,
                $this->pullContext($model, $registration->field),
            );
        }

        return $transitions;
    }

    /**
     * @return array<string, mixed>
     */
    private function pullContext(Model $model, string $field): array
    {
        $contexts = $this->contexts[$model] ?? [];
        $context = $contexts[$field] ?? [];

        unset($contexts[$field]);

        if ($contexts === []) {
            unset($this->contexts[$model]);
        } else {
            $this->contexts[$model] = $contexts;
        }

        return $context;
    }

    private function originalState(Model $model, RegisteredStateMachine $registration): ?StateEnum
    {
        // Registered fields are validated as native enum casts, so Eloquent returns enum cases here.
        $state = $model->getOriginal($registration->field);

        return $state instanceof StateEnum ? $state : null;
    }

    private function currentState(Model $model, RegisteredStateMachine $registration): ?StateEnum
    {
        $state = $model->getAttribute($registration->field);

        return $state instanceof StateEnum ? $state : null;
    }
}
