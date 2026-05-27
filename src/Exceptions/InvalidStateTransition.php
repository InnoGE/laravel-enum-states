<?php

namespace InnoGE\LaravelEnumStates\Exceptions;

use InnoGE\LaravelEnumStates\Contracts\StateEnum;

final class InvalidStateTransition extends EnumStatesException
{
    public static function make(string $model, string $field, StateEnum $from, StateEnum $to): self
    {
        return new self("Cannot transition [{$model}::{$field}] from [{$from->name}] to [{$to->name}].");
    }
}
