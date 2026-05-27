<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

final readonly class MarkOrderAsPaid
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<array-key, mixed>  $context
     */
    public function __invoke(Order $order, OrderStatus $from, OrderStatus $to, string $field, array $context): void
    {
        $this->audit->record('class', $order, $from, $to, $field, $context);
    }
}
