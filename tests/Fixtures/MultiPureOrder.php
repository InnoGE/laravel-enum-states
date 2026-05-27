<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

/**
 * @property PureStatus|null $status
 * @property PureStatus|null $review_status
 */
#[StateMachine('status', PureStatus::class)]
#[StateMachine('review_status', PureStatus::class)]
final class MultiPureOrder extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PureStatus::class,
            'review_status' => PureStatus::class,
        ];
    }
}
