<?php

namespace InnoGE\LaravelEnumStates\Contracts;

use InnoGE\LaravelEnumStates\StateMachine;
use UnitEnum;

interface StateEnum extends UnitEnum
{
    public static function configureStateMachine(StateMachine $machine): StateMachine;
}
