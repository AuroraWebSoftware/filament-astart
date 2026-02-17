<?php

namespace AuroraWebSoftware\FilamentAstart\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \AuroraWebSoftware\FilamentAstart\FilamentAstart
 */
class FilamentAstart extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \AuroraWebSoftware\FilamentAstart\FilamentAstart::class;
    }
}
