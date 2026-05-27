<?php

namespace InnoGE\LaravelEnumStates\Tests\Fixtures;

use Illuminate\Support\HtmlString;
use InnoGE\LaravelEnumStates\Contracts\StateEnum;
use InnoGE\LaravelEnumStates\StateMachine;

enum HtmlLabelStatus: string implements StateEnum
{
    case Draft = 'draft';

    public function getLabel(): HtmlString
    {
        return new HtmlString('<strong>Draft</strong>');
    }

    public static function configureStateMachine(StateMachine $machine): StateMachine
    {
        return $machine;
    }
}
