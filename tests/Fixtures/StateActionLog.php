<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

final class StateActionLog
{
    /** @var array<int, array<string, mixed>> */
    public static array $entries = [];

    public static function reset(): void
    {
        self::$entries = [];
    }

    /**
     * @param  array<array-key, mixed>  $context
     */
    public static function record(string $action, Order $order, OrderStatus $from, OrderStatus $to, string $field, array $context): void
    {
        self::$entries[] = compact('action', 'order', 'from', 'to', 'field', 'context');
    }
}
