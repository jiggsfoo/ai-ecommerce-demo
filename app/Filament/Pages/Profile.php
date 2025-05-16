<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule; // Alias to avoid conflict with model
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class Profile extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static string $view = 'filament.pages.profile';
    protected static ?string $navigationGroup = 'Account'; // Optional: Group in navigation
    protected static ?int $navigationSort = 99; // Optional: Sort order
    protected static ?string $title = 'My Profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->only(['name', 'email']));
    }

    public static function shouldRegisterNavigation(): bool
    {
        // This makes the page available but doesn't add it to the main navigation sidebar by default.
        // We will add it to the user menu instead.
        return true;
    }

    protected function getFormSchema(): array
    {
        return [
            Section::make('Profile Information')
                ->description('Update your account\'s profile information and email address.')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->disabled() // Per requirements, email is non-editable for now
                        ->helperText('Email address cannot be changed at this time.'),
                ]),
            Section::make('Update Password')
                ->description('Ensure your account is using a long, random password to stay secure.')
                ->schema([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->requiredWith('new_password') // Only required if new_password is provided
                        ->currentPassword() // Filament's built-in rule for current password
                        ->revealable(),
                    TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->requiredWith('current_password') // Makes it required if current_password is provided.
                        ->rules(function ($get) {
                            $newPasswordValue = $get('new_password');
                            if (!empty($newPasswordValue)) {
                                // If new_password has a value, apply confirmation and strength.
                                return [
                                    'confirmed',
                                    PasswordRule::defaults()
                                ];
                            }
                            return []; // No additional rules if new_password is empty.
                        })
                        ->revealable()
                        ->nullable(),
                    TextInput::make('new_password_confirmation')
                        ->label('Confirm New Password')
                        ->password()
                        ->requiredWith('new_password')
                        ->revealable()
                        ->nullable(),
                ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Changes')
                ->submit('save'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data')
            ->model(auth()->user());
    }

    public function save(): void
    {
        $this->form->getState(); // This validates and gets the latest form data

        $user = auth()->user();
        $formData = $this->form->getState();

        // Update Name
        if (isset($formData['name']) && $user->name !== $formData['name']) {
            $user->name = $formData['name'];
        }

        // Update Password
        if (!empty($formData['new_password'])) {
            // Current password validation is handled by ->currentPassword() rule
            $user->password = Hash::make($formData['new_password']);
        }

        $user->save();

        // Reset password fields after successful save
        $this->form->fill([
            'current_password' => null,
            'new_password' => null,
            'new_password_confirmation' => null,
            // Keep name and email filled
            'name' => $user->name,
            'email' => $user->email,
        ]);


        Notification::make()
            ->title('Profile updated successfully!')
            ->success()
            ->send();
    }
}
