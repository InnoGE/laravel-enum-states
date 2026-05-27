<?php

namespace InnoGE\LaravelEnumStates\Support;

use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;

final readonly class RegisteredStateMachine
{
    /**
     * @param  class-string<StateEnum>  $enum
     */
    public function __construct(
        public string $field,
        public string $enum,
        public StateMachine $machine,
    ) {}
}
