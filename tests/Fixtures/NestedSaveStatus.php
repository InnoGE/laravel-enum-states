<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use InnoGE\LaravelEnumStates\Concerns\TransitionsState;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;

enum NestedSaveStatus: string implements StateEnum
{
    use TransitionsState;

    case Open = 'open';
    case Done = 'done';

    public static function configureStateMachine(StateMachine $machine): StateMachine
    {
        return $machine
            ->default(self::Open)
            ->onEntering(self::Done, [
                MultiFieldNestedSaveAction::class,
            ])
            ->allow(self::Open, self::Done);
    }
}
