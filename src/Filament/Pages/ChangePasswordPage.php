<?php

namespace Hardikkhorasiya09\ChangePassword\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Closure;
use Hardikkhorasiya09\ChangePassword\ChangePasswordPlugin;
use BackedEnum;

class ChangePasswordPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'change-password::filament.pages.change-password';
    protected static ?string $slug = 'change-password';
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return ChangePasswordPlugin::canAccess();
    }

    public function mount(): void
    {
        // 
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('current_password')
                    ->password()
                    ->required()
                    ->revealable(true)
                    ->label('Current Password')
                    ->rules([
                        function () {
                            return function (string $attribute, $value, Closure $fail) {
                                if (! Hash::check($value, auth()->user()->password)) {
                                    $fail('Current password is incorrect.');
                                }
                            };
                        },
                    ]),

                Forms\Components\TextInput::make('new_password')
                    ->password()
                    ->required()
                    ->same('password_confirmation')
                    ->minLength(8)
                    ->label('New Password')
                    ->revealable(true),

                Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->required()
                    ->label('Confirm New Password')
                    ->revealable(true),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->form->validate();

        $data = $this->data;

        // Check if the current password is correct
        if (!Hash::check($data['current_password'], Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The provided password does not match your current password.',
            ]);
        }

        // Update the password if validation passes
        Auth::user()->update([
            'password' => $data['new_password'],
        ]);

        // Refill the form with the reset data
        $this->form->fill();

        session()->put([
            'password_hash_' . Auth::getDefaultDriver() => Auth::user()->password
        ]);

        // Success notification
        Notification::make()
            ->title('Password updated successfully!')
            ->success()
            ->send();
    }
}
