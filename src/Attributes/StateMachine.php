<?php

namespace InnoGE\LaravelEnumStates\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final readonly class StateMachine
{
    /**
     * @param  class-string  $enum
     */
    public function __construct(
        public string $field,
        public string $enum,
    ) {}
}
