<?php

namespace InnoGE\LaravelEnumStates\Exceptions;

final class StateMachineNotFound extends EnumStatesException
{
    public static function forField(string $model, string $field): self
    {
        return new self("No enum state machine is registered for [{$model}::{$field}].");
    }
}
