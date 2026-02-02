<?php

namespace AuroraWebSoftware\FilamentAstart\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TooltipTest extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-beaker';

    protected static string|null|\UnitEnum $navigationGroup = 'AStart';

    protected static ?string $navigationLabel = 'Tooltip Test';

    protected static ?string $title = 'Tooltip Component Test';

    protected string $view = 'filament-astart::pages.tooltip-test';

    protected static ?int $navigationSort = 100;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Tooltip Test')
                    ->description('CheckboxWithTooltip component test')
                    ->schema([
                        Checkbox::make('xxx')
                            ->label('Checkbox Test')
                            ->helperText('Bu yetki kullanıcıların bilgilerini değiştirmesine izin verir'),

                        Checkbox::make('can_edit_users')
                            ->label('Kullanıcıları Düzenleyebilir')
                            ->hintAction(
                                Action::make('x')
                                ->label('')
                                    ->icon('heroicon-o-information-circle')
                                    ->tooltip('Bu yetki kullanıcıların sistemde düzenleme yapmasına izin verir')
                            ),
                        Checkbox::make('normal_checkbox')
                            ->label('Normal Checkbox (Karşılaştırma için)'),

                        TextInput::make('test_input')
                            ->label('Test Input'),
                    ]),
            ])
            ->statePath('data');
    }
}
