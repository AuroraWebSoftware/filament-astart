<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use App\Models\User;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\RelationManagers\UserRolesRelationManager;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class UserResource extends Resource
{
    use AStartResourceAccessPolicy;

    protected static ?string $model = User::class;

    protected static string | null | \UnitEnum $navigationGroup = 'AStart';

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-user';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::user.users');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::user.user');
    }

    public static function form(Schema $schema): Schema
    {
        $op = $schema->getOperation();
        $auto = $op === 'view' ? 'none' : 'polite';

        return $schema
            ->extraAttributes(['autocomplete' => 'off'])
            ->schema([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-astart::user.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'autocapitalize' => 'none',
                                'spellcheck' => 'false',
                            ]),

                        TextInput::make('email')
                            ->label(__('filament-astart::user.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'autocapitalize' => 'none',
                                'autocorrect' => 'off',
                                'spellcheck' => 'false',
                            ]),

                        TextInput::make('password')
                            ->label(__('filament-astart::user.password'))
                            ->password()
                            ->rules([
                                'required',
                                'string',
                                Password::min(8)
                                    ->mixedCase()
                                    ->letters()
                                    ->numbers()
                                    ->symbols()
                                    ->uncompromised(),
                            ])
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (string $context): bool => $context === 'create')
                            ->columnSpan(1)
                            ->autocomplete(fn (string $context) => $context === 'create' ? 'new-password' : 'current-password')
                            ->revealable(),

                        PhoneInput::make('phone_number')
                            ->label(__('filament-astart::user.phone_number'))
                            ->initialCountry('tr')
                            ->countryOrder(['tr'])
                            ->strictMode()
                            ->required()
                            ->autoPlaceholder($auto)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('filament-astart::user.is_active'))
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->disabled(fn (string $context) => $context === 'view')
                            ->visible(fn () => \Illuminate\Support\Facades\Schema::hasColumn((new User)->getTable(), 'is_active'))
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d.m.Y H:i')->sortable()->label(__('filament-astart::user.created_at')),
            ])
            ->actions([
                ViewAction::make()->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record])),
                EditAction::make(),
                DeleteAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            UserRolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
