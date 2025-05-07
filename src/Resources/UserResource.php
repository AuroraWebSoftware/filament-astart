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
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label(__('filament-astart::user.name'))
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->label(__('filament-astart::user.email'))
                ->email()
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('password')
                ->label(__('filament-astart::user.password'))
                ->password()
                ->required(fn (string $context) => $context === 'create')
                ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->visible(fn (string $context): bool => in_array($context, ['create', 'edit'])),
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
                Tables\Actions\ViewAction::make()->url(fn ($record) => route('filament.admin.resources.users.view', ['record' => $record])),
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
