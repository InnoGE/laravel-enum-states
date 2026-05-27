<?php

namespace InnoGE\LaravelEnumStates\Support;

use Illuminate\Contracts\Container\Container;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;

final readonly class ActionInvoker
{
    public function __construct(private Container $container) {}

    public function invoke(callable|string $action, StateTransition $transition): mixed
    {
        return $this->container->call($this->resolve($action), [
            'model' => $transition->model,
            'from' => $transition->from,
            'to' => $transition->to,
            'field' => $transition->field,
            'context' => $transition->context,
            $transition->model::class => $transition->model,
        ]);
    }

    private function resolve(callable|string $action): callable
    {
        if (! is_string($action)) {
            return $action;
        }

        if (! class_exists($action)) {
            throw InvalidStateMachineConfiguration::unresolvableAction($action);
        }

        $resolved = $this->container->make($action);

        if (! is_callable($resolved)) {
            throw InvalidStateMachineConfiguration::unresolvableAction($action);
        }

        return $resolved;
    }
}
