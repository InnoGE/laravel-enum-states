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

        if (($context['save_inside_action'] ?? false) === true) {
            $order->save();
        }

        if (($context['record_dirty_inside_action'] ?? false) === true) {
            StateActionLog::$entries[] = [
                'action' => 'dirty',
                'dirty' => $order->getDirty(),
            ];
        }

        if (($context['cancel_inside_action'] ?? false) === true) {
            $order->status = OrderStatus::Cancelled;
            $order->save();
        }
    }
}
