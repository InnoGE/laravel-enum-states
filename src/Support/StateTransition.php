<?php

namespace InnoGE\LaravelEnumStates\Support;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;

final readonly class StateTransition
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Model $model,
        public string $field,
        public StateEnum $from,
        public StateEnum $to,
        public array $context = [],
    ) {}
}
