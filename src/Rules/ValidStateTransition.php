<?php

namespace InnoGE\LaravelEnumStates\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateOptions;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;

final readonly class ValidStateTransition implements ValidationRule
{
    public function __construct(
        private Model $model,
        private string $field,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $registration = StateMachineRegistry::forField($this->model, $this->field);

        $from = $this->model->getAttribute($this->field);
        $from = $from instanceof StateEnum ? $from : $registration->machine->defaultState();
        $to = StateOptions::coerce($registration->enum, $value);

        if ($from === null || $to === null) {
            $fail('The selected :attribute is invalid.');

            return;
        }

        if ($from === $to || $registration->machine->canTransition($from, $to)) {
            return;
        }

        $fail('The selected :attribute transition is invalid.');
    }
}
