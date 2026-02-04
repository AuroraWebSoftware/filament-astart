<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use App\Models\User;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource\RelationManagers\UserRolesRelationManager;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use AuroraWebSoftware\FilamentAstart\Traits\HasFiLoginIntegration;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class UserResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;
    use HasFiLoginIntegration;

    protected static ?string $model = User::class;

    protected static ?string $resourceKey = 'user';

    protected static string | null | \BackedEnum $navigationIcon = 'heroicon-o-user';

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.user.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.user.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.user.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $op = $schema->getOperation();
        $auto = $op === 'view' ? 'none' : 'polite';
        $isCreate = $op === 'create';

        return $schema
            ->extraAttributes(['autocomplete' => 'off'])
            ->schema([
                Section::make()
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'autocapitalize' => 'none',
                                'spellcheck' => 'false',
                            ]),

                        TextInput::make('email')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.email'))
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

                        PhoneInput::make('phone_number')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.phone_number'))
                            ->initialCountry('tr')
                            ->countryOrder(['tr'])
                            ->strictMode()
                            ->required()
                            ->autoPlaceholder($auto)
                            ->columnSpan(1),

                        Toggle::make('is_active')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.is_active'))
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

                // Password Section - Only for Create
                Section::make(__('filament-astart::filament-astart.resources.user.form.password_section'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->visible(fn (string $context): bool => $context === 'create')
                    ->schema([
                        Checkbox::make('generate_random_password')
                            ->label(__('filament-astart::filament-astart.resources.user.form.generate_random_password'))
                            ->helperText(__('filament-astart::filament-astart.resources.user.form.generate_random_password_helper'))
                            ->default(fn () => config('filament-astart.user_creation.force_random_password', false))
                            ->disabled(fn () => config('filament-astart.user_creation.force_random_password', false))
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $length = config('filament-astart.user_creation.random_password_length', 16);
                                if ($state) {
                                    $randomPassword = Str::password($length);
                                    $set('password', $randomPassword);
                                    $set('generated_password_display', $randomPassword);
                                } else {
                                    $set('password', '');
                                    $set('generated_password_display', '');
                                }
                            })
                            ->visible(fn () => config('filament-astart.user_creation.allow_random_password', true))
                            ->columnSpan(1),

                        Checkbox::make('send_credentials_email')
                            ->label(__('filament-astart::filament-astart.resources.user.form.send_credentials_email'))
                            ->helperText(__('filament-astart::filament-astart.resources.user.form.send_credentials_email_helper'))
                            ->default(fn () => config('filament-astart.user_creation.force_send_credentials_email', false))
                            ->disabled(fn () => config('filament-astart.user_creation.force_send_credentials_email', false))
                            ->visible(fn () => config('filament-astart.user_creation.allow_send_credentials_email', true))
                            ->columnSpan(1),

                        TextInput::make('generated_password_display')
                            ->label(__('filament-astart::filament-astart.resources.user.form.generated_password'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (Get $get): bool => (bool) $get('generate_random_password'))
                            ->suffixAction(
                                Action::make('copyPassword')
                                    ->icon('heroicon-o-clipboard')
                                    ->tooltip(__('filament-astart::filament-astart.resources.user.form.copy_password'))
                                    ->action(function ($state) {
                                        // Copy handled by JS
                                    })
                                    ->extraAttributes([
                                        'x-on:click' => 'navigator.clipboard.writeText($wire.data.generated_password_display)',
                                    ])
                            )
                            ->columnSpan(2),

                        TextInput::make('password')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.password'))
                            ->password()
                            ->rules([
                                'required',
                                'string',
                                PasswordRule::min(8)
                                    ->mixedCase()
                                    ->letters()
                                    ->numbers()
                                    ->symbols()
                                    ->uncompromised(),
                            ])
                            ->required(fn (Get $get) => ! $get('generate_random_password'))
                            ->disabled(fn (Get $get): bool => (bool) $get('generate_random_password'))
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (Get $get): bool => ! $get('generate_random_password') && ! config('filament-astart.user_creation.force_random_password', false))
                            ->columnSpan(2)
                            ->autocomplete('new-password')
                            ->revealable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.email'))
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->visible(fn () => \Illuminate\Support\Facades\Schema::hasColumn((new User)->getTable(), 'is_active')),

                // FiLogin: Lockout Status
                Tables\Columns\IconColumn::make('is_locked')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.is_locked'))
                    ->state(function ($record) {
                        if (! static::hasFiLogin()) {
                            return false;
                        }
                        $lockoutClass = static::getFiLoginUserLockoutModel();
                        $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                        return $lockoutClass::isLocked($record, $panelId);
                    })
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->visible(fn () => static::hasFiLogin()),

                // FiLogin: Active Sessions Count
                Tables\Columns\TextColumn::make('active_sessions_count')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.active_sessions'))
                    ->state(function ($record) {
                        if (! static::hasFiLogin()) {
                            return 0;
                        }
                        $sessionClass = static::getFiLoginSessionModel();

                        return $sessionClass::where('user_model_type', get_class($record))
                            ->where('user_id', $record->id)
                            ->whereNull('logged_out_at')
                            ->count();
                    })
                    ->badge()
                    ->color('info')
                    ->visible(fn () => static::hasFiLogin()),

                // FiLogin: Last Login
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.last_login'))
                    ->state(function ($record) {
                        if (! static::hasFiLogin()) {
                            return null;
                        }
                        $sessionClass = static::getFiLoginSessionModel();
                        $lastSession = $sessionClass::where('user_model_type', get_class($record))
                            ->where('user_id', $record->id)
                            ->latest('logged_in_at')
                            ->first();

                        return $lastSession?->logged_in_at;
                    })
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->visible(fn () => static::hasFiLogin()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-astart::filament-astart.resources.user.fields.created_at'))
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Active/Inactive Filter
                TernaryFilter::make('is_active')
                    ->label(__('filament-astart::filament-astart.resources.user.filters.is_active'))
                    ->visible(fn () => \Illuminate\Support\Facades\Schema::hasColumn((new User)->getTable(), 'is_active')),

                // FiLogin: Locked Users Filter
                TernaryFilter::make('is_locked')
                    ->label(__('filament-astart::filament-astart.resources.user.filters.is_locked'))
                    ->queries(
                        true: function (Builder $query) {
                            if (! static::hasFiLogin()) {
                                return $query;
                            }
                            $lockoutClass = static::getFiLoginUserLockoutModel();
                            $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                            return $query->whereIn('id', function ($subQuery) use ($panelId) {
                                $subQuery->select('user_id')
                                    ->from('filogin_user_lockouts')
                                    ->where('panel_id', $panelId)
                                    ->where(function ($q) {
                                        $q->where('is_permanent', true)
                                            ->orWhere('locked_until', '>', now());
                                    });
                            });
                        },
                        false: function (Builder $query) {
                            if (! static::hasFiLogin()) {
                                return $query;
                            }
                            $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                            return $query->whereNotIn('id', function ($subQuery) use ($panelId) {
                                $subQuery->select('user_id')
                                    ->from('filogin_user_lockouts')
                                    ->where('panel_id', $panelId)
                                    ->where(function ($q) {
                                        $q->where('is_permanent', true)
                                            ->orWhere('locked_until', '>', now());
                                    });
                            });
                        }
                    )
                    ->visible(fn () => static::hasFiLogin()),

                // FiLogin: No Login in X Days
                SelectFilter::make('inactive_days')
                    ->label(__('filament-astart::filament-astart.resources.user.filters.inactive_days'))
                    ->options([
                        '7' => __('filament-astart::filament-astart.resources.user.filters.inactive_7_days'),
                        '30' => __('filament-astart::filament-astart.resources.user.filters.inactive_30_days'),
                        '60' => __('filament-astart::filament-astart.resources.user.filters.inactive_60_days'),
                        '90' => __('filament-astart::filament-astart.resources.user.filters.inactive_90_days'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! static::hasFiLogin() || empty($data['value'])) {
                            return $query;
                        }

                        $days = (int) $data['value'];
                        $cutoffDate = now()->subDays($days);

                        return $query->whereNotIn('id', function ($subQuery) use ($cutoffDate) {
                            $subQuery->select('user_id')
                                ->from('filogin_sessions')
                                ->where('logged_in_at', '>=', $cutoffDate);
                        });
                    })
                    ->visible(fn () => static::hasFiLogin()),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

                EditAction::make(),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
