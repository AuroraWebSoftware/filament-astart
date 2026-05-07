<?php

namespace AuroraWebSoftware\FilamentAstart\Forms\Components;

use AuroraWebSoftware\FilamentAstart\Forms\Concerns\HasUserSelectOptions;
use Filament\Forms\Components\Select;

class UserSelect extends Select
{
    use HasUserSelectOptions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureUserOptions();
    }
}
