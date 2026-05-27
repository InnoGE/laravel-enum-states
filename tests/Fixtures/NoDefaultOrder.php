<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use InnoGE\LaravelEnumStates\Attributes\StateMachine;

/**
 * @property NoDefaultStatus|null $status
 */
#[StateMachine('status', NoDefaultStatus::class)]
final class NoDefaultOrder extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => NoDefaultStatus::class,
        ];
    }
}
