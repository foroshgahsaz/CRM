<?php

namespace App\Filament\Auth;

use App\Exceptions\WorkflowException;
use App\Services\OtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public string $loginMethod = 'email';

    public bool $mobileOtpSent = false;

    public function switchToEmailLogin(): void
    {
        $this->loginMethod = 'email';
        $this->mobileOtpSent = false;
        $this->form->fill();
    }

    public function switchToMobileLogin(): void
    {
        $this->loginMethod = 'mobile';
        $this->mobileOtpSent = false;
        $this->form->fill();
    }

    public function sendStaffLoginOtp(OtpService $otp): void
    {
        try {
            $this->rateLimit(5, 60);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $mobile = (string) ($this->data['mobile'] ?? '');

        if ($mobile === '') {
            throw ValidationException::withMessages([
                'data.mobile' => 'شماره موبایل را وارد کنید.',
            ]);
        }

        try {
            $otp->issueForStaff($mobile, request()->ip());
        } catch (WorkflowException $exception) {
            throw ValidationException::withMessages([
                'data.mobile' => $exception->getMessage(),
            ]);
        }

        $this->mobileOtpSent = true;

        Notification::make()
            ->title('رمز یک‌بارمصرف ارسال شد')
            ->body(config('crm.otp_debug')
                ? 'برای تست، OTP در storage/logs/laravel.log با کلید crm.otp_debug ثبت می‌شود.'
                : 'پیامک را بررسی کنید.')
            ->success()
            ->send();
    }

    public function authenticate(): ?LoginResponse
    {
        if ($this->loginMethod === 'mobile') {
            return $this->authenticateWithMobile();
        }

        return parent::authenticate();
    }

    protected function authenticateWithMobile(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();
        $mobile = (string) ($data['mobile'] ?? '');
        $code = (string) ($data['code'] ?? '');

        if (! $this->mobileOtpSent) {
            throw ValidationException::withMessages([
                'data.mobile' => 'ابتدا رمز یک‌بارمصرف را دریافت کنید.',
            ]);
        }

        try {
            $user = app(OtpService::class)->verifyStaffLogin($mobile, $code);
        } catch (WorkflowException $exception) {
            throw ValidationException::withMessages([
                'data.code' => $exception->getMessage(),
            ]);
        }

        Filament::auth()->login($user, (bool) ($data['remember'] ?? false));
        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function form(Schema $schema): Schema
    {
        if ($this->loginMethod === 'mobile') {
            return $schema->components([
                TextInput::make('mobile')
                    ->label('شماره موبایل')
                    ->tel()
                    ->required()
                    ->maxLength(20),
                TextInput::make('code')
                    ->label('رمز یک‌بارمصرف')
                    ->numeric()
                    ->minLength(4)
                    ->maxLength(8)
                    ->visible($this->mobileOtpSent)
                    ->required($this->mobileOtpSent),
                $this->getRememberFormComponent(),
            ]);
        }

        return $schema->components([
            parent::getEmailFormComponent()->label('ایمیل (نام کاربری)'),
            $this->getPasswordFormComponent()->label('رمز عبور'),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getFormActions(): array
    {
        if ($this->loginMethod === 'mobile') {
            $actions = [
                $this->getSwitchLoginMethodAction(),
            ];

            if (! $this->mobileOtpSent) {
                $actions[] = $this->getSendOtpFormAction();
            } else {
                $actions[] = $this->getAuthenticateFormAction()->label('ورود');
            }

            return $actions;
        }

        return [
            $this->getAuthenticateFormAction(),
            $this->getSwitchLoginMethodAction(),
        ];
    }

    protected function getSendOtpFormAction(): Action
    {
        return Action::make('sendStaffOtp')
            ->label('دریافت رمز یک‌بارمصرف')
            ->action('sendStaffLoginOtp');
    }

    protected function getSwitchLoginMethodAction(): Action
    {
        if ($this->loginMethod === 'email') {
            return Action::make('useMobile')
                ->label('ورود با موبایل')
                ->link()
                ->action('switchToMobileLogin');
        }

        return Action::make('useEmail')
            ->label('ورود با ایمیل و رمز')
            ->link()
            ->action('switchToEmailLogin');
    }
}
