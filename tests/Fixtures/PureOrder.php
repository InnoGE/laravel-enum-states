<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

/**
 * @property PureStatus|null $status
 */
#[StateMachine('status', PureStatus::class)]
final class PureOrder extends Model
{
    protected $table = 'pure_orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => PureStatus::class,
        ];
    }
}
