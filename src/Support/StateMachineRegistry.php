<?php

namespace InnoGE\LaravelEnumStates\Support;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine as StateMachineAttribute;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;
use InnoGE\LaravelEnumStates\Exceptions\StateMachineNotFound;
use InnoGE\LaravelEnumStates\StateMachine;
use ReflectionClass;

final class StateMachineRegistry
{
    /** @var array<class-string, array<string, RegisteredStateMachine>> */
    private static array $registrations = [];

    /** @var array<class-string<StateEnum>, StateMachine> */
    private static array $machines = [];

    /**
     * @param  Model|class-string<Model>  $model
     * @return array<string, RegisteredStateMachine>
     */
    public static function forModel(Model|string $model): array
    {
        $modelClass = is_string($model) ? $model : $model::class;

        return self::$registrations[$modelClass] ??= self::readModelRegistrations($modelClass);
    }

    /**
     * @param  Model|class-string<Model>  $model
     */
    public static function forField(Model|string $model, string $field): RegisteredStateMachine
    {
        return self::forModel($model)[$field] ?? throw StateMachineNotFound::forField(
            is_string($model) ? $model : $model::class,
            $field,
        );
    }

    /**
     * @param  class-string<StateEnum>  $enum
     */
    public static function forEnum(string $enum): StateMachine
    {
        return self::$machines[$enum] ??= $enum::configureStateMachine(new StateMachine($enum));
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, RegisteredStateMachine>
     */
    private static function readModelRegistrations(string $model): array
    {
        $registrations = [];
        $reflections = [];
        $reflection = new ReflectionClass($model);

        do {
            $reflections[] = $reflection;
            $reflection = $reflection->getParentClass();
        } while ($reflection instanceof ReflectionClass);

        foreach (array_reverse($reflections) as $reflection) {
            $seenFields = [];

            foreach ($reflection->getAttributes(StateMachineAttribute::class) as $attribute) {
                $stateMachine = $attribute->newInstance();

                if (in_array($stateMachine->field, $seenFields, true)) {
                    throw InvalidStateMachineConfiguration::duplicateField($reflection->name, $stateMachine->field);
                }

                $seenFields[] = $stateMachine->field;

                if (! is_subclass_of($stateMachine->enum, StateEnum::class)) {
                    throw InvalidStateMachineConfiguration::enumMustImplementStateEnum($stateMachine->enum);
                }

                /** @var class-string<StateEnum> $enum */
                $enum = $stateMachine->enum;
                self::ensureModelCastsFieldToEnum($model, $stateMachine->field, $enum);

                $registrations[$stateMachine->field] = new RegisteredStateMachine(
                    $stateMachine->field,
                    $enum,
                    self::forEnum($enum),
                );
            }
        }

        return $registrations;
    }

    /**
     * @param  class-string<StateEnum>  $enum
     * @param  class-string<Model>  $model
     */
    private static function ensureModelCastsFieldToEnum(string $model, string $field, string $enum): void
    {
        $casts = (new $model)->getCasts();
        $cast = $casts[$field] ?? null;

        if ($cast === null) {
            throw InvalidStateMachineConfiguration::missingEnumCast($model, $field, $enum);
        }

        if ($cast !== $enum) {
            throw InvalidStateMachineConfiguration::mismatchedEnumCast(
                $model,
                $field,
                $enum,
                is_string($cast) ? $cast : get_debug_type($cast),
            );
        }
    }
}
