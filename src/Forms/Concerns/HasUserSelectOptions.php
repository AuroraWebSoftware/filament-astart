<?php

namespace AuroraWebSoftware\FilamentAstart\Forms\Concerns;

trait HasUserSelectOptions
{
    protected bool $showEmail = false;

    public function withEmail(bool $showEmail = true): static
    {
        $this->showEmail = $showEmail;

        return $this;
    }

    protected function configureUserOptions(): void
    {
        $this->searchable();

        $avatarEnabled = config('filament-astart.avatar.enabled', false);

        if ($avatarEnabled) {
            $this->allowHtml();
        }

        $this->options(function () use ($avatarEnabled): array {
            $userModel = $this->resolveUserModel();

            return $userModel::query()
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn ($user) => [
                    $user->getKey() => $this->formatUserLabel($user, $avatarEnabled),
                ])
                ->toArray();
        });

        $this->getSearchResultsUsing(function (string $search) use ($avatarEnabled): array {
            $userModel = $this->resolveUserModel();

            return $userModel::query()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->limit(50)
                ->get()
                ->mapWithKeys(fn ($user) => [
                    $user->getKey() => $this->formatUserLabel($user, $avatarEnabled),
                ])
                ->toArray();
        });

        $this->getOptionLabelUsing(function ($value) use ($avatarEnabled): ?string {
            $userModel = $this->resolveUserModel();
            $user = $userModel::find($value);

            if (! $user) {
                return null;
            }

            return $this->formatUserLabel($user, $avatarEnabled);
        });
    }

    protected function formatUserLabel(mixed $user, bool $withAvatar): string
    {
        if (! $withAvatar) {
            $label = e($user->name);
            if ($this->showEmail) {
                $label .= ' ('.e($user->email).')';
            }

            return $label;
        }

        $avatarUrl = e(filament()->getUserAvatarUrl($user));

        $emailHtml = $this->showEmail
            ? '<span class="astart-user-select-email">'.e($user->email).'</span>'
            : '';

        return '<div class="flex items-center gap-x-3">'
            .'<img src="'.$avatarUrl.'" class="astart-user-select-avatar" />'
            .'<div>'
            .'<span class="astart-user-select-name">'.e($user->name).'</span>'
            .$emailHtml
            .'</div>'
            .'</div>';
    }

    /**
     * @return class-string
     */
    protected function resolveUserModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }
}
