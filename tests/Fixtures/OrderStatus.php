<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use InnoGE\LaravelEnumStates\Concerns\TransitionsState;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;
use RuntimeException;

enum OrderStatus: string implements StateEnum
{
    use TransitionsState;

    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Shipped => 'Shipped',
            self::Cancelled => 'Cancelled',
        };
    }

    public static function configureStateMachine(StateMachine $machine): StateMachine
    {
        return $machine
            ->default(self::Pending)
            ->onEntering(self::Paid, [
                MarkOrderAsPaid::class,
            ])
            ->onLeaving(self::Paid, [
                LeavingPaid::class,
            ])
            ->allow(self::Pending, [
                self::Paid,
                self::Cancelled,
            ])
            ->allow([self::Pending, self::Paid], self::Cancelled, actions: [
                /**
                 * @param  array<string, mixed>  $context
                 */
                function (Order $order, self $from, self $to, string $field, array $context, AuditLogger $audit): void {
                    if (($context['fail'] ?? false) === true) {
                        throw new RuntimeException('Transition action failed.');
                    }

                    $audit->record('closure', $order, $from, $to, $field, $context);
                },
            ])
            ->allow(self::Paid, self::Shipped);
    }
}
