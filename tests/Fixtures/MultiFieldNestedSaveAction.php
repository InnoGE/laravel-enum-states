<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

final class MultiFieldNestedSaveAction
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __invoke(MultiFieldNestedSaveOrder $order, NestedSaveStatus $from, NestedSaveStatus $to, string $field, array $context): void
    {
        StateActionLog::$entries[] = compact('order', 'from', 'to', 'field', 'context');

        if (($context['save_inside_action'] ?? false) === true) {
            $order->save();
        }
    }
}
