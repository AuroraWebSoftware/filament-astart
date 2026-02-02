<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Notifications\UserCredentialsNotification;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use AStartPageLabels;

    protected static string $resource = UserResource::class;

    protected static ?string $resourceKey = 'user';

    protected static ?string $pageType = 'create';

    protected ?string $plainPassword = null;

    protected bool $shouldSendEmail = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Check if random password should be generated
        $useRandomPassword = ! empty($data['generate_random_password'])
            || config('filament-astart.user_creation.force_random_password', false);

        // Generate password if needed
        if ($useRandomPassword && empty($data['password'])) {
            $length = config('filament-astart.user_creation.random_password_length', 16);
            $randomPassword = \Illuminate\Support\Str::password($length);
            $data['password'] = \Illuminate\Support\Facades\Hash::make($randomPassword);
            $this->plainPassword = $randomPassword;
        } elseif (! empty($data['generated_password_display'])) {
            // Password was generated in form
            $this->plainPassword = $data['generated_password_display'];
            // Ensure password is hashed if not already
            if (! empty($data['password']) && ! str_starts_with($data['password'], '$2y$')) {
                $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
            }
        }

        // Check if we should send credentials email
        $shouldSendEmail = ! empty($data['send_credentials_email'])
            || config('filament-astart.user_creation.force_send_credentials_email', false);

        if ($shouldSendEmail && $this->plainPassword) {
            $this->shouldSendEmail = true;
        }

        // Remove non-database fields
        unset(
            $data['generate_random_password'],
            $data['send_credentials_email'],
            $data['generated_password_display']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->shouldSendEmail && $this->plainPassword) {
            $this->sendCredentialsEmail();
        }
    }

    protected function sendCredentialsEmail(): void
    {
        $user = $this->record;
        $loginUrl = filament()->getCurrentPanel()?->getLoginUrl() ?? url('/login');

        try {
            $user->notify(new UserCredentialsNotification($this->plainPassword, $loginUrl));

            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.credentials_email_sent'))
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('filament-astart::filament-astart.resources.user.messages.credentials_email_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
