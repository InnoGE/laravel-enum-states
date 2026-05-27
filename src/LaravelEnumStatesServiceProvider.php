<?php

namespace InnoGE\LaravelEnumStates;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Support\StateMachineHandler;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelEnumStatesServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-enum-states');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(StateMachineHandler::class);
    }

    public function packageBooted(): void
    {
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        /** @var Dispatcher $events */
        $events = $this->app->make('events');

        $events->listen('eloquent.saving: *', function (string $event, array $payload): void {
            if (($model = $this->registeredModelFrom($payload)) === null) {
                return;
            }

            $this->app->make(StateMachineHandler::class)->saving($model);
        });

        $events->listen('eloquent.saved: *', function (string $event, array $payload): void {
            if (($model = $this->registeredModelFrom($payload)) === null) {
                return;
            }

            $this->app->make(StateMachineHandler::class)->saved($model);
        });
    }

    /**
     * @param  array<array-key, mixed>  $payload
     */
    private function registeredModelFrom(array $payload): ?Model
    {
        $model = $payload[0] ?? null;

        if (! $model instanceof Model) {
            return null;
        }

        $registrations = StateMachineRegistry::forModel($model);

        return $registrations === [] ? null : $model;
    }
}
