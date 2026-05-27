<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

#[StateMachine('status', OrderStatus::class)]
#[StateMachine('status', OrderStatus::class)]
final class DuplicateFieldOrder extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
        ];
    }
}
