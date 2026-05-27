<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateMachineConfiguration;
use InnoGE\LaravelEnumStates\Exceptions\InvalidStateTransition;
use InnoGE\LaravelEnumStates\Exceptions\StateMachineNotFound;
use InnoGE\LaravelEnumStates\Filament\EnumStateSelect;
use InnoGE\LaravelEnumStates\Rules\ValidStateTransition;
use InnoGE\LaravelEnumStates\StateMachine;
use InnoGE\LaravelEnumStates\StateOptions;
use InnoGE\LaravelEnumStates\Support\ActionInvoker;
use InnoGE\LaravelEnumStates\Support\StateMachineRegistry;
use InnoGE\LaravelEnumStates\Support\StateTransition;
use InnoGE\LaravelEnumStates\Tests\Fixtures\BadEnumOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\DuplicateFieldOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\HtmlLabelStatus;
use InnoGE\LaravelEnumStates\Tests\Fixtures\LeavingPaid;
use InnoGE\LaravelEnumStates\Tests\Fixtures\MismatchedCastOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\MissingCastOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\MultiPureOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\MultiStatusOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\NoDefaultOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\NoDefaultStatus;
use InnoGE\LaravelEnumStates\Tests\Fixtures\Order;
use InnoGE\LaravelEnumStates\Tests\Fixtures\OrderStatus;
use InnoGE\LaravelEnumStates\Tests\Fixtures\PlainOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\PureOrder;
use InnoGE\LaravelEnumStates\Tests\Fixtures\PureStatus;
use InnoGE\LaravelEnumStates\Tests\Fixtures\StateActionLog;

function fixtureOrderStatus(Order|MultiStatusOrder $order): OrderStatus
{
    $status = $order->getAttribute('status');

    if (! $status instanceof OrderStatus) {
        throw new RuntimeException('Expected order status to be initialized.');
    }

    return $status;
}

function fixturePureStatus(MultiPureOrder $order, string $field): PureStatus
{
    $status = $order->getAttribute($field);

    if (! $status instanceof PureStatus) {
        throw new RuntimeException('Expected pure status to be initialized.');
    }

    return $status;
}

beforeEach(function (): void {
    StateActionLog::reset();
});

it('ignores models without the state machine attribute', function (): void {
    $order = PlainOrder::create(['status' => 'anything']);

    $order->status = 'updated';
    $order->save();

    expect($order->refresh()->status)->toBe('updated');
});

it('throws when transitionTo targets an unregistered field', function (): void {
    $order = PlainOrder::create(['status' => 'anything']);

    expect(fn () => OrderStatus::Pending->transitionTo($order, 'status', OrderStatus::Paid))
        ->toThrow(StateMachineNotFound::class);
});

it('throws when no state machine is registered for a field', function (): void {
    expect(fn () => StateMachineRegistry::forField(PlainOrder::class, 'status'))
        ->toThrow(StateMachineNotFound::class);
});

it('ignores wildcard eloquent events whose payload is not a model', function (): void {
    app('events')->dispatch('eloquent.saving: FakeModel', [new stdClass]);
    app('events')->dispatch('eloquent.saved: FakeModel', [new stdClass]);

    expect(true)->toBeTrue();
});

it('applies enum defaults on save', function (): void {
    $order = new Order;

    expect($order->status)->toBeNull();

    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(StateActionLog::$entries)->toBe([]);
});

it('does not apply a default when the state machine has none', function (): void {
    $order = NoDefaultOrder::create();

    expect($order->refresh()->status)->toBeNull()
        ->and(StateOptions::enabled(NoDefaultStatus::class, null))->toBe([]);
});

it('reapplies the enum default when a registered field is nulled on an existing record', function (): void {
    // Defaults are applied on every save, not just inserts: nulling a registered
    // field and saving silently restores the default rather than persisting null.
    $order = Order::create();

    $order->status = null;
    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Pending)
        ->and(StateActionLog::$entries)->toBe([]);
});

it('does not reapply the default on a no-op save of a populated field', function (): void {
    $order = Order::create(['status' => OrderStatus::Paid]);
    StateActionLog::reset();

    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and(StateActionLog::$entries)->toBe([]);
});

it('accepts valid transitions through native enum assignment', function (): void {
    $order = Order::create();

    $order->status = OrderStatus::Paid;
    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and(StateActionLog::$entries)->toHaveCount(1)
        ->and(StateActionLog::$entries[0]['action'])->toBe('class')
        ->and(StateActionLog::$entries[0]['from'])->toBe(OrderStatus::Pending)
        ->and(StateActionLog::$entries[0]['to'])->toBe(OrderStatus::Paid)
        ->and(StateActionLog::$entries[0]['field'])->toBe('status')
        ->and(StateActionLog::$entries[0]['context'])->toBe([]);
});

it('treats direct creation into a non-default state as a transition from the default', function (): void {
    $order = Order::create(['status' => OrderStatus::Paid]);

    expect($order->refresh()->status)->toBe(OrderStatus::Paid)
        ->and(StateActionLog::$entries)->toHaveCount(1)
        ->and(StateActionLog::$entries[0]['from'])->toBe(OrderStatus::Pending)
        ->and(StateActionLog::$entries[0]['to'])->toBe(OrderStatus::Paid);
});

it('throws invalid transitions on save instead of assignment', function (): void {
    $order = Order::create();

    $order->status = OrderStatus::Shipped;

    expect($order->status)->toBe(OrderStatus::Shipped)
        ->and(fn () => $order->save())->toThrow(InvalidStateTransition::class);
});

it('clears explicit context after a failed validation', function (): void {
    $order = Order::create();

    fixtureOrderStatus($order)->transitionTo($order, 'status', OrderStatus::Shipped, reason: 'invalid');

    expect(fn () => $order->save())->toThrow(InvalidStateTransition::class);

    $order->status = OrderStatus::Paid;
    $order->save();

    expect(StateActionLog::$entries)->toHaveCount(1)
        ->and(StateActionLog::$entries[0]['context'])->toBe([]);
});

it('passes explicit transition context to closure actions', function (): void {
    $order = Order::create();

    fixtureOrderStatus($order)->transitionTo($order, 'status', OrderStatus::Cancelled, reason: 'customer_request');
    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(StateActionLog::$entries)->toHaveCount(1)
        ->and(StateActionLog::$entries[0]['action'])->toBe('closure')
        ->and(StateActionLog::$entries[0]['context'])->toBe(['reason' => 'customer_request']);
});

it('targets transition context by field', function (): void {
    $order = MultiStatusOrder::create();
    StateActionLog::reset();

    fixtureOrderStatus($order)->transitionTo($order, 'status', OrderStatus::Cancelled, reason: 'explicit_field');
    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->refresh()->review_status)->toBe(OrderStatus::Pending)
        ->and(StateActionLog::$entries)->toHaveCount(1)
        ->and(StateActionLog::$entries[0]['field'])->toBe('status')
        ->and(StateActionLog::$entries[0]['context'])->toBe(['reason' => 'explicit_field']);
});

it('runs leaving entering and transition actions in order', function (): void {
    $order = Order::create(['status' => OrderStatus::Paid]);
    StateActionLog::reset();

    fixtureOrderStatus($order)->transitionTo($order, 'status', OrderStatus::Cancelled, reason: 'customer_request');
    $order->save();

    expect($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and(array_column(StateActionLog::$entries, 'action'))->toBe(['leaving', 'closure'])
        ->and(StateActionLog::$entries[0]['context'])->toBe(['reason' => 'customer_request'])
        ->and(StateActionLog::$entries[1]['context'])->toBe(['reason' => 'customer_request']);
});

it('clears only consumed context while validating multi-field transitions', function (): void {
    $order = MultiPureOrder::create();

    fixturePureStatus($order, 'status')->transitionTo($order, 'status', PureStatus::Closed, context: 'first');
    fixturePureStatus($order, 'review_status')->transitionTo($order, 'review_status', PureStatus::Closed, context: 'second');

    $order->save();

    expect($order->refresh()->status)->toBe(PureStatus::Closed)
        ->and($order->refresh()->review_status)->toBe(PureStatus::Closed);
});

it('returns leaving entering and transition actions in order', function (): void {
    $actions = StateMachineRegistry::forEnum(OrderStatus::class)
        ->actionsFor(OrderStatus::Paid, OrderStatus::Cancelled);

    expect($actions[0])->toBe(LeavingPaid::class)
        ->and($actions[1])->toBeCallable();
});

it('merges actions when the same transition is allowed more than once', function (): void {
    $first = fn (): null => null;
    $second = fn (): null => null;

    $actions = (new StateMachine(OrderStatus::class))
        ->allow(OrderStatus::Pending, OrderStatus::Paid, [$first])
        ->allow(OrderStatus::Pending, OrderStatus::Paid, [$second])
        ->actionsFor(OrderStatus::Pending, OrderStatus::Paid);

    expect($actions)->toBe([$first, $second]);
});

it('rolls back action failures when the application saves inside a transaction', function (): void {
    $order = Order::create();

    expect(function () use ($order): void {
        DB::transaction(function () use ($order): void {
            fixtureOrderStatus($order)->transitionTo($order, 'status', OrderStatus::Cancelled, fail: true);
            $order->save();
        });
    })->toThrow(RuntimeException::class);

    expect($order->refresh()->status)->toBe(OrderStatus::Pending);
});

it('exposes enum case and static transition helper methods', function (): void {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Shipped))->toBeFalse()
        ->and(OrderStatus::Pending->transitionableStates())->toBe([OrderStatus::Paid, OrderStatus::Cancelled])
        ->and(OrderStatus::canTransition(OrderStatus::Paid, OrderStatus::Shipped))->toBeTrue()
        ->and(OrderStatus::transitionableStatesFrom(OrderStatus::Paid))->toBe([OrderStatus::Shipped, OrderStatus::Cancelled]);
});

it('throws when checking transitions across enum classes', function (): void {
    expect(fn () => OrderStatus::Pending->canTransitionTo(PureStatus::Closed))
        ->toThrow(InvalidStateMachineConfiguration::class);
});

it('builds form options and disabled values for state selects', function (): void {
    expect(StateOptions::all(OrderStatus::class))->toBe([
        'pending' => 'Pending',
        'paid' => 'Paid',
        'shipped' => 'Shipped',
        'cancelled' => 'Cancelled',
    ])->and(StateOptions::enabled(OrderStatus::class, OrderStatus::Pending))->toBe([
        'pending' => 'Pending',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ])->and(StateOptions::disabledValues(OrderStatus::class, OrderStatus::Pending))->toBe([
        'shipped',
    ])->and(StateOptions::all(PureStatus::class))->toBe([
        'Open' => 'Open',
        'Closed' => 'Closed',
    ])->and(StateOptions::all(HtmlLabelStatus::class))->toBe([
        'draft' => '<strong>Draft</strong>',
    ])->and(StateOptions::currentState(OrderStatus::class, null))->toBe(OrderStatus::Pending)
        ->and(StateOptions::coerce(OrderStatus::class, null))->toBeNull()
        ->and(StateOptions::coerce(OrderStatus::class, []))->toBeNull()
        ->and(StateOptions::coerce(PureStatus::class, 'Missing'))->toBeNull();
});

it('provides a Filament select that disables untransitionable states', function (): void {
    $select = EnumStateSelect::make('status')
        ->stateEnum(OrderStatus::class)
        ->currentState(OrderStatus::Pending);

    expect($select->getOptions())->toBe([
        'pending' => 'Pending',
        'paid' => 'Paid',
        'shipped' => 'Shipped',
        'cancelled' => 'Cancelled',
    ])->and($select->isOptionDisabled('pending', 'Pending'))->toBeFalse()
        ->and($select->isOptionDisabled('paid', 'Paid'))->toBeFalse()
        ->and($select->isOptionDisabled('shipped', 'Shipped'))->toBeTrue()
        ->and($select->isOptionDisabled('cancelled', 'Cancelled'))->toBeFalse();
});

it('supports Filament select edge cases', function (): void {
    $closureState = EnumStateSelect::make('status')
        ->stateEnum(OrderStatus::class)
        ->currentState(fn (): OrderStatus => OrderStatus::Paid);

    $invalidState = EnumStateSelect::make('status')
        ->stateEnum(OrderStatus::class)
        ->currentState(fn (): array => ['invalid']);

    expect(fn () => EnumStateSelect::make('status')->stateEnum(fn (): string => PlainOrder::class)->getStateEnum())
        ->toThrow(InvalidStateMachineConfiguration::class)
        ->and($closureState->isOptionDisabled('pending', 'Pending'))->toBeTrue()
        ->and($invalidState->isOptionDisabled('paid', 'Paid'))->toBeFalse();
});

it('uses Laravel native enum query compatibility', function (): void {
    $pending = Order::create();
    $paid = Order::create(['status' => OrderStatus::Paid]);

    expect(Order::where('status', OrderStatus::Paid)->pluck('id')->all())->toBe([$paid->id])
        ->and(Order::whereNot('status', OrderStatus::Paid)->pluck('id')->all())->toBe([$pending->id]);
});

it('supports pure enum states', function (): void {
    $order = PureOrder::create(['status' => PureStatus::Open]);

    $order->status = PureStatus::Closed;
    $order->save();

    expect($order->refresh()->status)->toBe(PureStatus::Closed)
        ->and(PureStatus::Open->canTransitionTo(PureStatus::Closed))->toBeTrue()
        ->and(PureOrder::where('status', PureStatus::Closed)->pluck('id')->all())->toBe([$order->id]);
});

it('validates requested transitions', function (): void {
    $order = Order::create();

    $valid = Validator::make(
        ['status' => OrderStatus::Paid->value],
        ['status' => [new ValidStateTransition($order, 'status')]],
    );

    $invalid = Validator::make(
        ['status' => OrderStatus::Shipped->value],
        ['status' => [new ValidStateTransition($order, 'status')]],
    );

    expect($valid->passes())->toBeTrue()
        ->and($invalid->passes())->toBeFalse();
});

it('validates pure enum transitions by case name', function (): void {
    $order = PureOrder::create();

    $valid = Validator::make(
        ['status' => PureStatus::Closed->name],
        ['status' => [new ValidStateTransition($order, 'status')]],
    );

    $invalid = Validator::make(
        ['status' => 'Missing'],
        ['status' => [new ValidStateTransition($order, 'status')]],
    );

    expect($valid->passes())->toBeTrue()
        ->and($invalid->passes())->toBeFalse();
});

it('throws typed exceptions for invalid state machine configuration', function (Closure $create): void {
    expect($create)->toThrow(InvalidStateMachineConfiguration::class);
})->with([
    fn (): MissingCastOrder => MissingCastOrder::create(),
    fn (): MismatchedCastOrder => MismatchedCastOrder::create(),
    fn (): DuplicateFieldOrder => DuplicateFieldOrder::create(),
    fn (): BadEnumOrder => BadEnumOrder::create(),
]);

it('rejects action strings that are not invokable classes', function (string $action): void {
    $order = Order::create();
    $transition = new StateTransition($order, 'status', OrderStatus::Pending, OrderStatus::Paid);

    expect(fn () => app(ActionInvoker::class)->invoke($action, $transition))
        ->toThrow(InvalidStateMachineConfiguration::class);
})->with([
    'missing class' => ['App\\Actions\\DoesNotExist'],
    'non-invokable class' => [StateActionLog::class],
    'callable string' => ['strlen'],
]);
