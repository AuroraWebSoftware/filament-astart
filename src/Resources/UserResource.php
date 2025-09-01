<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use App\Models\User;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\RelationManagers\UserRolesRelationManager;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class UserResource extends Resource
{
    use AStartResourceAccessPolicy;

    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'AStart';

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::user.users');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::user.user');
    }

    public static function form(Form $form): Form
    {
        $op = $form->getOperation();
        $auto = $op === 'view' ? 'none' : 'polite';

        return $form
            ->extraAttributes(['autocomplete' => 'off'])
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('filament-astart::user.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'autocapitalize' => 'none',
                                'spellcheck' => 'false',
                            ]),


                        Forms\Components\TextInput::make('email')
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

                        Forms\Components\TextInput::make('password')
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
                            ->required(fn(string $context) => $context === 'create')
                            ->dehydrateStateUsing(fn($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->visible(fn(string $context): bool => in_array($context, ['create', 'edit']))
                            ->columnSpan(1)
                            ->autocomplete(fn(string $context) => $context === 'create' ? 'new-password' : 'current-password')
                            ->revealable(),

                        PhoneInput::make('phone_number')
                            ->label(__('filament-astart::user.phone_number'))
                            ->initialCountry('tr')
                            ->countryOrder(['tr'])
                            ->strictMode()
                            ->required()
                            ->autoPlaceholder($auto)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('filament-astart::user.is_active'))
                            ->default(true)
                            ->inline(false)
                            ->onIcon('heroicon-m-check')
                            ->offIcon('heroicon-m-x-mark')
                            ->onColor('success')
                            ->offColor('danger')
                            ->disabled(fn(string $context) => $context === 'view')
                            ->visible(fn() => Schema::hasColumn((new User)->getTable(), 'is_active'))
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
                Tables\Actions\ViewAction::make()->url(fn($record) => route('filament.admin.resources.users.view', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

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
