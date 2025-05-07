<?php

namespace AuroraWebSoftware\FilamentAstart\Examples;

use AuroraWebSoftware\ArFlow\Contacts\StateableModelContract;
use AuroraWebSoftware\ArFlow\Traits\HasState;
use Illuminate\Database\Eloquent\Model;

class ArflowExample extends Model implements StateableModelContract
{
    use HasState;

    public static function supportedWorkflows(): array
    {
        return ['simple'];
    }
}
