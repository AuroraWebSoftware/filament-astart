<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditHistoryResource\Pages;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use AuroraWebSoftware\FilamentAstart\Traits\HasLogiAuditIntegration;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use AuroraWebSoftware\LogiAudit\Models\LogiAuditHistory;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DbSchema;

class LogiAuditHistoryResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;
    use HasLogiAuditIntegration;

    protected static ?string $model = LogiAuditHistory::class;

    protected static ?string $resourceKey = 'logiaudit_history';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 91;

    public static function canViewAny(): bool
    {
        return static::hasLogiAudit() && AAuthUtil::can(static::getPermissionSlug('view_any'));
    }

    public static function canView(Model $record): bool
    {
        return static::hasLogiAudit() && AAuth::can(static::getPermissionSlug('view'), $record);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-astart::filament-astart.resources.logiaudit_history.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.logiaudit_history.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.logiaudit_history.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => DbSchema::hasColumn('logiaudit_history', 'causer_type')
                ? $query->with('causer')
                : $query)
            ->columns([
                Tables\Columns\TextColumn::make('action')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('table')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.table'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('model')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.model'))
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model_id')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.model_id'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('causer_name')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.user'))
                    ->state(function ($record) {
                        if (! $record->user_id) {
                            return '-';
                        }

                        if (DbSchema::hasColumn('logiaudit_history', 'causer_type') && $record->causer_type) {
                            return $record->causer?->name ?? "#{$record->user_id}";
                        }

                        return "#{$record->user_id}";
                    }),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.ip_address'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.created_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.filters.action'))
                    ->options([
                        'created' => __('filament-astart::filament-astart.resources.logiaudit_history.actions.created'),
                        'updated' => __('filament-astart::filament-astart.resources.logiaudit_history.actions.updated'),
                        'deleted' => __('filament-astart::filament-astart.resources.logiaudit_history.actions.deleted'),
                        'restored' => __('filament-astart::filament-astart.resources.logiaudit_history.actions.restored'),
                    ])
                    ->multiple(),

                SelectFilter::make('table')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_history.filters.table'))
                    ->options(fn () => static::getModel()::query()
                        ->distinct()
                        ->pluck('table', 'table')
                        ->toArray())
                    ->multiple()
                    ->searchable(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.filters.from')),
                        DatePicker::make('until')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('filament-astart::filament-astart.resources.logiaudit_history.sections.detail'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('action')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.action'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'created' => 'success',
                                'updated' => 'warning',
                                'deleted' => 'danger',
                                'restored' => 'info',
                                default => 'gray',
                            }),

                        TextEntry::make('table')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.table'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('model')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.model'))
                            ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-'),

                        TextEntry::make('model_id')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.model_id')),

                        TextEntry::make('causer_name')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.user'))
                            ->state(function ($record) {
                                if (! $record->user_id) {
                                    return '-';
                                }

                                $causer = $record->causer;

                                return $causer?->name ?? "#{$record->user_id}";
                            }),

                        TextEntry::make('ip_address')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.ip_address'))
                            ->placeholder('-')
                            ->copyable(),

                        TextEntry::make('created_at')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_history.fields.created_at'))
                            ->dateTime('d.m.Y H:i:s'),
                    ]),

                Section::make(__('filament-astart::filament-astart.resources.logiaudit_history.sections.changes'))
                    ->columnSpanFull()
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        ViewEntry::make('changes')
                            ->view('filament-astart::infolists.logiaudit-changes')
                            ->state(fn ($record) => [
                                'column' => $record->column ?? [],
                                'old_value' => $record->old_value ?? [],
                                'new_value' => $record->new_value ?? [],
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLogiAuditHistories::route('/'),
            'view' => Pages\ViewLogiAuditHistory::route('/{record}'),
        ];
    }
}
