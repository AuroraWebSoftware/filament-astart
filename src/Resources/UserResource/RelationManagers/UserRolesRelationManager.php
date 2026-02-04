<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\RelationManagers;

use AuroraWebSoftware\FilamentAstart\Model\OrganizationNode;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class UserRolesRelationManager extends RelationManager
{
    protected static string $relationship = 'roles';

    protected static ?string $label = 'Roller';

    protected static ?string $title = 'Tanımlı Roller';

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema;
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Rol')->searchable(),
                Tables\Columns\TextColumn::make('pivot.organization_node_id')
                    ->label('Organization Node')
                    ->formatStateUsing(
                        fn ($state, $record) => $record->pivot->organization_node_id
                        ? OrganizationNode::find($record->pivot->organization_node_id)?->name
                        : '—'
                    ),
                Tables\Columns\TextColumn::make('organization_scope')
                    ->label('Organization Scope')
                    ->formatStateUsing(
                        fn ($state, $record) => $record->pivot->organization_node_id
                        ? OrganizationNode::find($record->pivot->organization_node_id)?->organizationScope?->name
                        : '—'
                    ),
            ])
            ->actions([
                Action::make('delete')
                    ->label('Sil')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record, Action $action) {

                        $userId = $this->ownerRecord->id;
                        $roleId = $record->id;
                        $nodeId = $record->pivot->organization_node_id;

                        DB::table('user_role_organization_node')
                            ->where('user_id', $userId)
                            ->where('role_id', $roleId)
                            ->where('organization_node_id', $nodeId)
                            ->delete();

                        $action->success();
                        $this->ownerRecord->refresh();
                    }),
            ]);
    }
}
