<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use InnoGE\LaravelEnumStates\Concerns\TransitionsState;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;

enum NoDefaultStatus: string implements StateEnum
{
    use TransitionsState;

    case Start = 'start';
    case End = 'end';

    public static function configureStateMachine(StateMachine $machine): StateMachine
    {
        return $machine->allow(self::Start, self::End);
    }
}
