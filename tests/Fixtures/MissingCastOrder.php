<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

#[StateMachine('status', OrderStatus::class)]
final class MissingCastOrder extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $guarded = [];
}
