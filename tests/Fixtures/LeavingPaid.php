<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

final readonly class LeavingPaid
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function __invoke(Order $order, OrderStatus $from, OrderStatus $to, string $field, array $context): void
    {
        $this->audit->record('leaving', $order, $from, $to, $field, $context);
    }
}
