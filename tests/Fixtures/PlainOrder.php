<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $status
 */
final class PlainOrder extends Model
{
    protected $table = 'plain_orders';

    public $timestamps = false;

    protected $guarded = [];
}
