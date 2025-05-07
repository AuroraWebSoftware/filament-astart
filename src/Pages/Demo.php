<?php

namespace AuroraWebSoftware\FilamentAstart\Pages;

use AuroraWebSoftware\FilamentAstart\Traits\AStartCustomPageAccessPolicy;
use Filament\Pages\Page;

class Demo extends Page
{
    use AStartCustomPageAccessPolicy;

    protected static string $permission = 'view_demo';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament-astart::pages.demo';
}
