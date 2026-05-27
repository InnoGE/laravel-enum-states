<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

#[StateMachine('status', BadStatus::class)]
final class BadEnumOrder extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => BadStatus::class,
        ];
    }
}
