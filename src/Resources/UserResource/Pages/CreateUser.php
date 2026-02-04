<?php

namespace AuroraWebSoftware\FilamentAstart\Resources\UserResource\Pages;

use AuroraWebSoftware\FilamentAstart\Notifications\UserCredentialsNotification;
use AuroraWebSoftware\FilamentAstart\Resources\UserResource;
use AuroraWebSoftware\FilamentAstart\Traits\AStartPageLabels;
use AuroraWebSoftware\FilamentAstart\Traits\HasFiLoginIntegration;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use AStartPageLabels;
    use HasFiLoginIntegration;

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
            $randomPassword = $this->generatePassword($length);
            $data['password'] = \Illuminate\Support\Facades\Hash::make($randomPassword);
            $this->plainPassword = $randomPassword;
        } elseif (! empty($data['generated_password_display'])) {
            // Password was generated in form
            $this->plainPassword = $data['generated_password_display'];
            // Ensure password is hashed if not already
            if (! empty($data['password'])) {
                $hashInfo = password_get_info($data['password']);
                if ($hashInfo['algo'] === null) {
                    $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
                }
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
        // Force password reset on first login (FiLogin feature)
        if (config('filament-astart.user_creation.force_password_reset', false) && static::hasFiLogin()) {
            $policyClass = static::getFiLoginPasswordPolicyModel();
            if ($policyClass) {
                $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';
                $policyClass::forceChange($this->record, $panelId);
            }
        }

        if ($this->shouldSendEmail && $this->plainPassword) {
            $this->sendCredentialsEmail();
        }
    }

    /**
     * Generate a readable password with limited symbols (not at the beginning)
     */
    protected function generatePassword(int $length = 16): string
    {
        $letters = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $numbers = '23456789';
        $symbols = '!@#$%&*';

        // Start with a letter
        $password = $letters[random_int(0, strlen($letters) - 1)];

        // Add more letters and numbers for the base
        $baseChars = $letters . $numbers;
        $baseLength = $length - 3; // Reserve space for 2 symbols

        for ($i = 1; $i < $baseLength; $i++) {
            $password .= $baseChars[random_int(0, strlen($baseChars) - 1)];
        }

        // Add 2 symbols at random positions (not at the beginning)
        for ($i = 0; $i < 2; $i++) {
            $position = random_int(2, strlen($password));
            $symbol = $symbols[random_int(0, strlen($symbols) - 1)];
            $password = substr($password, 0, $position) . $symbol . substr($password, $position);
        }

        return $password;
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
