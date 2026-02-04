<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\AAuth\Models\Role;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\HasFiLoginIntegration;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ViewUser extends ViewRecord
{
    use AStartPageLabels;
    use HasFiLoginIntegration;

    protected static string $resource = UserResource::class;

    protected static ?string $resourceKey = 'user';

    protected static ?string $pageType = 'view';

    /**
     * Check if user is locked (helper to avoid DRY violation)
     */
    protected function isUserLocked($record = null, ?string $panelId = null): bool
    {
        if (! static::hasFiLogin()) {
            return false;
        }

        $record = $record ?? $this->record;
        $panelId = $panelId ?? (filament()->getCurrentPanel()?->getId() ?? 'admin');
        $lockoutClass = static::getFiLoginUserLockoutModel();

        $lockout = $lockoutClass::where('user_model_type', get_class($record))
            ->where('user_id', $record->getAuthIdentifier())
            ->where('panel_id', $panelId)
            ->first();

        return $lockout && ($lockout->is_permanent || ($lockout->locked_until && $lockout->locked_until->isFuture()));
    }

    /**
     * Toggle lock/unlock account
     */
    public function toggleLockAction(): void
    {
        if (! static::hasFiLogin()) {
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.filogin_required'))
                ->danger()
                ->send();

            return;
        }

        try {
            $lockoutClass = static::getFiLoginUserLockoutModel();
            $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

            $isLocked = $this->isUserLocked();

            if ($isLocked) {
                // Unlock using FiLogin API
                $lockout = $lockoutClass::where('user_model_type', get_class($this->record))
                    ->where('user_id', $this->record->getAuthIdentifier())
                    ->where('panel_id', $panelId)
                    ->first();

                $lockout?->unlock();

                Notification::make()
                    ->title(__('filament-astart::filament-astart.resources.user.messages.account_unlocked'))
                    ->success()
                    ->send();
            } else {
                // Lock permanently using FiLogin API
                $lockout = $lockoutClass::getOrCreate($this->record, $panelId);
                $lockout->lockUser(0, true); // 0 minutes, permanent=true

                Notification::make()
                    ->title(__('filament-astart::filament-astart.resources.user.messages.account_locked'))
                    ->success()
                    ->send();
            }

            $this->dispatch('close-modal', id: 'securityActions');
            $this->js('window.location.reload()');
        } catch (\Exception $e) {
            report($e);
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.action_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * Force user to change password on next login
     */
    public function forcePasswordChangeAction(): void
    {
        if (! static::hasFiLogin()) {
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.filogin_required'))
                ->danger()
                ->send();

            return;
        }

        try {
            $policyClass = static::getFiLoginPasswordPolicyModel();
            $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

            // Use FiLogin's forceChange method
            $policyClass::forceChange($this->record, $panelId);

            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.password_change_forced'))
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'securityActions');
            $this->js('window.location.reload()');
        } catch (\Exception $e) {
            report($e);
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.action_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * Terminate all user sessions
     */
    public function terminateSessionsAction(): void
    {
        if (! static::hasFiLogin()) {
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.filogin_required'))
                ->danger()
                ->send();

            return;
        }

        try {
            $sessionClass = static::getFiLoginSessionModel();

            // Get active session IDs before marking them as logged out
            $sessionIds = $sessionClass::where('user_model_type', get_class($this->record))
                ->where('user_id', $this->record->id)
                ->whereNull('logged_out_at')
                ->pluck('session_id')
                ->filter()
                ->toArray();

            // Mark sessions as logged out in FiLogin
            $sessionClass::where('user_model_type', get_class($this->record))
                ->where('user_id', $this->record->id)
                ->whereNull('logged_out_at')
                ->update(['logged_out_at' => now()]);

            // Destroy actual sessions (works with all session drivers: redis, database, file)
            $handler = session()->getHandler();
            foreach ($sessionIds as $sessionId) {
                try {
                    $handler->destroy($sessionId);
                } catch (\Exception $e) {
                    report($e);
                }
            }

            // Invalidate remember token to logout "remember me" sessions
            if (method_exists($this->record, 'setRememberToken')) {
                $this->record->setRememberToken(Str::random(60));
                $this->record->save();
            }

            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.sessions_terminated'))
                ->success()
                ->send();

            $this->dispatch('close-modal', id: 'securityActions');
            $this->js('window.location.reload()');
        } catch (\Exception $e) {
            report($e);
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.action_failed'))
                ->danger()
                ->send();
        }
    }

    /**
     * Send password reset email to user
     */
    public function sendPasswordResetAction(): void
    {
        try {
            $user = $this->record;
            $broker = \Illuminate\Support\Facades\Password::broker(filament()->getAuthPasswordBroker());

            $status = $broker->sendResetLink(
                ['email' => $user->email],
                function ($user, string $token): void {
                    $notification = app(\Filament\Auth\Notifications\ResetPassword::class, ['token' => $token]);
                    $notification->url = filament()->getResetPasswordUrl($token, $user);
                    $user->notifyNow($notification);
                }
            );

            if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
                Notification::make()
                    ->title(__('filament-astart::filament-astart.resources.user.messages.password_reset_sent'))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title(__('filament-astart::filament-astart.resources.user.messages.password_reset_failed'))
                    ->body(__($status))
                    ->danger()
                    ->send();
            }

            $this->dispatch('close-modal', id: 'securityActions');
        } catch (\Exception $e) {
            report($e);
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.action_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // User Info Section
                Section::make(__('filament-astart::filament-astart.resources.user.sections.user_info'))
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.name'))
                            ->weight(FontWeight::Bold),

                        TextEntry::make('email')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.email'))
                            ->copyable(),

                        TextEntry::make('phone_number')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.phone_number'))
                            ->default('-'),

                        TextEntry::make('is_active')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.is_active'))
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? __('filament-astart::filament-astart.resources.user.status.active') : __('filament-astart::filament-astart.resources.user.status.inactive'))
                            ->color(fn ($state) => $state ? 'success' : 'danger')
                            ->visible(fn () => \Illuminate\Support\Facades\Schema::hasColumn($this->record->getTable(), 'is_active')),

                        TextEntry::make('created_at')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.created_at'))
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('updated_at')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.updated_at'))
                            ->dateTime('d.m.Y H:i'),
                    ]),

                // FiLogin: Security Info Section
                Section::make(__('filament-astart::filament-astart.resources.user.sections.security_info'))
                    ->description(__('filament-astart::filament-astart.resources.user.sections.security_info_desc'))
                    ->icon('heroicon-o-shield-check')
                    ->columns(3)
                    ->columnSpanFull()
                    ->visible(fn () => static::hasFiLogin())
                    ->schema([
                        TextEntry::make('lockout_status')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.lockout_status'))
                            ->badge()
                            ->state(fn ($record) => $this->isUserLocked($record)
                                ? __('filament-astart::filament-astart.resources.user.status.locked')
                                : __('filament-astart::filament-astart.resources.user.status.unlocked'))
                            ->color(fn ($record) => $this->isUserLocked($record) ? 'danger' : 'success'),

                        TextEntry::make('active_sessions')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.active_sessions'))
                            ->badge()
                            ->color('info')
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return 0;
                                }
                                $sessionClass = static::getFiLoginSessionModel();

                                return $sessionClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->whereNull('logged_out_at')
                                    ->count();
                            }),

                        TextEntry::make('last_login')
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

                                return $lastSession?->logged_in_at?->format('d.m.Y H:i') ?? '-';
                            }),

                        TextEntry::make('last_login_ip')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.last_login_ip'))
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return null;
                                }
                                $sessionClass = static::getFiLoginSessionModel();
                                $lastSession = $sessionClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->latest('logged_in_at')
                                    ->first();

                                return $lastSession?->ip_address ?? '-';
                            }),

                        TextEntry::make('last_login_location')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.last_login_location'))
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return null;
                                }
                                $sessionClass = static::getFiLoginSessionModel();
                                $lastSession = $sessionClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->latest('logged_in_at')
                                    ->first();

                                if (! $lastSession) {
                                    return '-';
                                }

                                $parts = array_filter([
                                    $lastSession->city,
                                    $lastSession->country_name ?? $lastSession->country,
                                ]);

                                return implode(', ', $parts) ?: '-';
                            }),

                        TextEntry::make('failed_attempts')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.failed_attempts'))
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'warning' : 'success')
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return 0;
                                }
                                $lockoutClass = static::getFiLoginUserLockoutModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $lockout = $lockoutClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                return $lockout?->failed_attempts ?? 0;
                            }),

                        TextEntry::make('password_changed_at')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.password_changed_at'))
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return '-';
                                }
                                $policyClass = static::getFiLoginPasswordPolicyModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $policy = $policyClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                return $policy?->last_changed_at?->format('d.m.Y H:i') ?? '-';
                            }),

                        TextEntry::make('password_expires_at')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.password_expires_at'))
                            ->badge()
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return '-';
                                }
                                $policyClass = static::getFiLoginPasswordPolicyModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $policy = $policyClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                if (! $policy?->expires_at) {
                                    return __('filament-astart::filament-astart.resources.user.status.no_expiry');
                                }

                                if ($policy->isExpired()) {
                                    return __('filament-astart::filament-astart.resources.user.status.expired');
                                }

                                return $policy->expires_at->format('d.m.Y');
                            })
                            ->color(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return 'gray';
                                }
                                $policyClass = static::getFiLoginPasswordPolicyModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $policy = $policyClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                if (! $policy?->expires_at) {
                                    return 'gray';
                                }

                                if ($policy->isExpired()) {
                                    return 'danger';
                                }

                                if ($policy->isExpiringSoon()) {
                                    return 'warning';
                                }

                                return 'success';
                            }),

                        TextEntry::make('must_change_password')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.must_change_password'))
                            ->badge()
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return __('filament-astart::filament-astart.resources.user.status.no');
                                }
                                $policyClass = static::getFiLoginPasswordPolicyModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $policy = $policyClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                return $policy?->must_change_password
                                    ? __('filament-astart::filament-astart.resources.user.status.yes')
                                    : __('filament-astart::filament-astart.resources.user.status.no');
                            })
                            ->color(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return 'gray';
                                }
                                $policyClass = static::getFiLoginPasswordPolicyModel();
                                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

                                $policy = $policyClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('panel_id', $panelId)
                                    ->first();

                                return $policy?->must_change_password ? 'warning' : 'success';
                            }),

                        TextEntry::make('trusted_devices_count')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.trusted_devices'))
                            ->badge()
                            ->color('info')
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return 0;
                                }
                                $deviceClass = static::getFiLoginKnownDeviceModel();

                                return $deviceClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->where('is_trusted', true)
                                    ->count();
                            }),

                        TextEntry::make('mfa_status')
                            ->label(__('filament-astart::filament-astart.resources.user.fields.mfa_status'))
                            ->badge()
                            ->state(function ($record) {
                                $methods = [];
                                if ($record->app_authentication_secret) {
                                    $methods[] = 'App';
                                }
                                if ($record->has_email_authentication ?? false) {
                                    $methods[] = 'Email';
                                }
                                if ($record->has_sms_authentication ?? false) {
                                    $methods[] = 'SMS';
                                }

                                return count($methods) > 0
                                    ? implode(', ', $methods)
                                    : __('filament-astart::filament-astart.resources.user.status.mfa_disabled');
                            })
                            ->color(function ($record) {
                                $hasAny = $record->app_authentication_secret
                                    || ($record->has_email_authentication ?? false)
                                    || ($record->has_sms_authentication ?? false);

                                return $hasAny ? 'success' : 'gray';
                            }),
                    ]),

                // FiLogin: Recent Login History Section
                Section::make(__('filament-astart::filament-astart.resources.user.sections.login_history'))
                    ->description(__('filament-astart::filament-astart.resources.user.sections.login_history_desc'))
                    ->icon('heroicon-o-clock')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->visible(fn () => static::hasFiLogin())
                    ->schema([
                        ViewEntry::make('login_history')
                            ->view('filament-astart::infolists.login-history')
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return collect();
                                }
                                $attemptClass = static::getFiLoginLoginAttemptModel();

                                return $attemptClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->latest()
                                    ->limit(10)
                                    ->get();
                            }),
                    ]),

                // FiLogin: Active Sessions Section
                Section::make(__('filament-astart::filament-astart.resources.user.sections.active_sessions'))
                    ->description(__('filament-astart::filament-astart.resources.user.sections.active_sessions_desc'))
                    ->icon('heroicon-o-computer-desktop')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->visible(fn () => static::hasFiLogin())
                    ->schema([
                        ViewEntry::make('sessions')
                            ->view('filament-astart::infolists.active-sessions')
                            ->state(function ($record) {
                                if (! static::hasFiLogin()) {
                                    return collect();
                                }
                                $sessionClass = static::getFiLoginSessionModel();

                                return $sessionClass::where('user_model_type', get_class($record))
                                    ->where('user_id', $record->id)
                                    ->whereNull('logged_out_at')
                                    ->latest('logged_in_at')
                                    ->get();
                            }),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';

        // FiLogin: Security Actions (Single Button with Modal) - First/Left
        if (static::hasFiLogin()) {
            $isLocked = $this->isUserLocked();

            $actions[] = Action::make('securityActions')
                ->label(__('filament-astart::filament-astart.resources.user.actions.security_actions'))
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->modalHeading(__('filament-astart::filament-astart.resources.user.actions.security_actions'))
                ->modalDescription(__('filament-astart::filament-astart.resources.user.actions.security_actions_desc', ['name' => $this->record->name]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel(__('filament-astart::filament-astart.resources.user.actions.close'))
                ->modalWidth('md')
                ->modalContent(view('filament-astart::modals.security-actions', [
                    'record' => $this->record,
                    'isLocked' => $isLocked,
                    'panelId' => $panelId,
                ]));

        }

        // Edit Action - Middle
        $actions[] = EditAction::make();

        // Assign Role Action - Last/Right
        $actions[] = Action::make('assignRole')
            ->label(__('filament-astart::filament-astart.resources.user.actions.assign_role'))
            ->icon('heroicon-o-user-plus')
            ->color('info')
            ->form(function () {
                $form = [];

                $form[] = Select::make('role_id')
                    ->label(__('filament-astart::filament-astart.resources.user.form.select_role'))
                    ->options(
                        Role::query()
                            ->where('status', 'active')
                            ->pluck('name', 'id')
                    )
                    ->required()
                    ->live()
                    ->searchable();

                $scopes = DB::table('organization_scopes')
                    ->where('status', 'active')
                    ->orderBy('level')
                    ->get();

                foreach ($scopes as $index => $scope) {
                    $form[] = Select::make("org_level_{$scope->id}")
                        ->label($scope->name)
                        ->options(function (Get $get) use ($index, $scope, $scopes) {
                            if ($index === 0) {
                                return DB::table('organization_nodes')
                                    ->where('organization_scope_id', $scope->id)
                                    ->pluck('name', 'id');
                            }

                            // Find the last selected parent from previous scopes
                            $parentId = null;
                            for ($i = $index - 1; $i >= 0; $i--) {
                                $prevScope = $scopes[$i];
                                $selectedId = $get("org_level_{$prevScope->id}");
                                if ($selectedId) {
                                    $parentId = $selectedId;

                                    break;
                                }
                            }

                            if (! $parentId) {
                                return [];
                            }

                            return DB::table('organization_nodes')
                                ->where('parent_id', $parentId)
                                ->where('organization_scope_id', $scope->id)
                                ->pluck('name', 'id');
                        })
                        ->live()
                        ->searchable()
                        ->hidden(function (Get $get) use ($scope) {
                            $roleId = $get('role_id');
                            if (! $roleId) {
                                return true;
                            }

                            $roleScopeLevel = DB::table('roles')
                                ->leftJoin('organization_scopes', 'organization_scopes.id', '=', 'roles.organization_scope_id')
                                ->where('roles.id', $roleId)
                                ->value('organization_scopes.level');

                            return ! $roleScopeLevel || $roleScopeLevel < $scope->level;
                        });
                }

                return $form;
            })
            ->action(function (array $data) {
                $user = $this->record;

                $selectedOrgNodeId = null;

                $scopeIds = DB::table('organization_scopes')
                    ->where('status', 'active')
                    ->orderByDesc('level')
                    ->pluck('id');

                foreach ($scopeIds as $scopeId) {
                    $key = "org_level_{$scopeId}";
                    if (! empty($data[$key])) {
                        $selectedOrgNodeId = $data[$key];

                        break;
                    }
                }

                $user->roles()->syncWithoutDetaching([
                    $data['role_id'] => [
                        'organization_node_id' => $selectedOrgNodeId,
                    ],
                ]);

                Notification::make()
                    ->title(__('filament-astart::filament-astart.resources.user.messages.role_added_success'))
                    ->success()
                    ->send();

                $this->dispatch('close-modal', id: 'securityActions');
                $this->js('window.location.reload()');
            });

        return $actions;
    }
}
