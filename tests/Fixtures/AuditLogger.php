<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

final class AuditLogger
{
    /**
     * @param  array<array-key, mixed>  $context
     */
    public function record(string $action, Order $order, OrderStatus $from, OrderStatus $to, string $field, array $context): void
    {
        StateActionLog::record($action, $order, $from, $to, $field, $context);
    }
}
