<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

/**
 * @property NestedSaveStatus|null $status
 * @property NestedSaveStatus|null $review_status
 */
#[StateMachine('status', NestedSaveStatus::class)]
#[StateMachine('review_status', NestedSaveStatus::class)]
final class MultiFieldNestedSaveOrder extends Model
{
    protected $table = 'multi_status_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => NestedSaveStatus::class,
            'review_status' => NestedSaveStatus::class,
        ];
    }
}
