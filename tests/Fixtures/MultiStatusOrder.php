<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

/**
 * @property OrderStatus|null $status
 * @property OrderStatus|null $review_status
 */
#[StateMachine('status', OrderStatus::class)]
#[StateMachine('review_status', OrderStatus::class)]
final class MultiStatusOrder extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'review_status' => OrderStatus::class,
        ];
    }
}
