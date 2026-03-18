<?php

namespace AuroraWebSoftware\FilamentAstart\Resources;

use AuroraWebSoftware\AAuth\Facades\AAuth;
use AuroraWebSoftware\FilamentAstart\Resources\LogiAuditLogResource\Pages;
use AuroraWebSoftware\FilamentAstart\Traits\AStartNavigationGroup;
use AuroraWebSoftware\FilamentAstart\Traits\AStartResourceAccessPolicy;
use AuroraWebSoftware\FilamentAstart\Traits\HasLogiAuditIntegration;
use AuroraWebSoftware\FilamentAstart\Utils\AAuthUtil;
use AuroraWebSoftware\LogiAudit\Models\LogiAuditLog;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as DbSchema;

class LogiAuditLogResource extends Resource
{
    use AStartNavigationGroup;
    use AStartResourceAccessPolicy;
    use HasLogiAuditIntegration;

    protected static ?string $model = LogiAuditLog::class;

    protected static ?string $resourceKey = 'logiaudit_log';

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 90;

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
        return __('filament-astart::filament-astart.resources.logiaudit_log.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.logiaudit_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-astart::filament-astart.resources.logiaudit_log.plural');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('logged_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('level')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.level'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'emergency', 'alert', 'critical', 'error' => 'danger',
                        'warning' => 'warning',
                        'notice' => 'info',
                        'info' => 'success',
                        'debug' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('tag')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.tag'))
                    ->badge()
                    ->color('info')
                    ->placeholder('-')
                    ->searchable()
                    ->visible(fn () => DbSchema::hasColumn('logiaudit_logs', 'tag')),

                Tables\Columns\TextColumn::make('message')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.message'))
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->message)
                    ->searchable(),

                Tables\Columns\TextColumn::make('model_type')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.model_type'))
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.ip_address'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('trace_id')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.trace_id'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('deletable')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.deletable'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('logged_at')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.logged_at'))
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.expires_at'))
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.filters.level'))
                    ->options([
                        'emergency' => 'Emergency',
                        'alert' => 'Alert',
                        'critical' => 'Critical',
                        'error' => 'Error',
                        'warning' => 'Warning',
                        'notice' => 'Notice',
                        'info' => 'Info',
                        'debug' => 'Debug',
                    ])
                    ->multiple(),

                SelectFilter::make('tag')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.filters.tag'))
                    ->options(fn () => static::getModel()::query()
                        ->whereNotNull('tag')
                        ->distinct()
                        ->pluck('tag', 'tag')
                        ->toArray())
                    ->multiple()
                    ->searchable()
                    ->visible(fn () => DbSchema::hasColumn('logiaudit_logs', 'tag')),

                TernaryFilter::make('deletable')
                    ->label(__('filament-astart::filament-astart.resources.logiaudit_log.filters.deletable')),

                Tables\Filters\Filter::make('logged_at')
                    ->form([
                        DatePicker::make('from')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.filters.from')),
                        DatePicker::make('until')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $query, $date) => $query->whereDate('logged_at', '>=', $date))
                            ->when($data['until'], fn (Builder $query, $date) => $query->whereDate('logged_at', '<=', $date));
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
                Section::make(__('filament-astart::filament-astart.resources.logiaudit_log.sections.log_detail'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('level')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.level'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'emergency', 'alert', 'critical', 'error' => 'danger',
                                'warning' => 'warning',
                                'notice' => 'info',
                                'info' => 'success',
                                'debug' => 'gray',
                                default => 'gray',
                            }),

                        TextEntry::make('tag')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.tag'))
                            ->badge()
                            ->color('info')
                            ->placeholder('-')
                            ->visible(fn () => DbSchema::hasColumn('logiaudit_logs', 'tag')),

                        TextEntry::make('ip_address')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.ip_address'))
                            ->placeholder('-')
                            ->copyable(),

                        TextEntry::make('message')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.message'))
                            ->weight(FontWeight::Bold)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-astart::filament-astart.resources.logiaudit_log.sections.model_info'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('model_type')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.model_type'))
                            ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                            ->placeholder('-'),

                        TextEntry::make('model_id')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.model_id'))
                            ->placeholder('-'),

                        TextEntry::make('trace_id')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.trace_id'))
                            ->placeholder('-')
                            ->copyable(),
                    ]),

                Section::make(__('filament-astart::filament-astart.resources.logiaudit_log.sections.context'))
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('context')
                            ->label('')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) {
                                    return '-';
                                }

                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            })
                            ->markdown()
                            ->prose(false)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('filament-astart::filament-astart.resources.logiaudit_log.sections.timestamps'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('logged_at')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.logged_at'))
                            ->dateTime('d.m.Y H:i:s'),

                        TextEntry::make('expires_at')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.expires_at'))
                            ->dateTime('d.m.Y H:i:s')
                            ->placeholder('-'),

                        TextEntry::make('deletable')
                            ->label(__('filament-astart::filament-astart.resources.logiaudit_log.fields.deletable'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? __('filament-astart::filament-astart.resources.logiaudit_log.status.yes') : __('filament-astart::filament-astart.resources.logiaudit_log.status.no'))
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
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
            'index' => Pages\ListLogiAuditLogs::route('/'),
            'view' => Pages\ViewLogiAuditLog::route('/{record}'),
        ];
    }
}
