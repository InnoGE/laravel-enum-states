<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use InnoGE\LaravelEnumStates\Concerns\TransitionsState;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;

enum PureStatus implements StateEnum
{
    use TransitionsState;

    case Open;
    case Closed;

    public static function configureStateMachine(StateMachine $machine): StateMachine
    {
        return $machine
            ->default(self::Open)
            ->allow(self::Open, self::Closed);
    }
}
