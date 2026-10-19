<?php

namespace App\Filament\Pages;

use App\Services\AuditLogService;
use App\Services\SettingsService;
use App\Services\SmsService;
use App\Support\MobileNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'تنظیمات سامانه';

    protected static ?string $title = 'تنظیمات سامانه';

    protected static string|UnitEnum|null $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 5;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('settings.manage') || $user->can('sms_settings.manage'));
    }

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $this->form->fill([
            'company_name' => $settings->get('company_name'),
            'company_tagline' => $settings->get('company_tagline'),
            'company_description' => $settings->get('company_description'),
            'contact_phone' => $settings->get('contact_phone'),
            'contact_email' => $settings->get('contact_email'),
            'contact_address' => $settings->get('contact_address'),
            'required_documents_text' => $settings->get('required_documents_text'),
            'process_guide' => $settings->get('process_guide'),
            'company_logo' => $settings->get('company_logo'),
            'sms_enabled' => $settings->getBool('sms_enabled', false),
            'otp_ttl_seconds' => $settings->getInt('otp_ttl_seconds', 120),
            'otp_max_attempts' => $settings->getInt('otp_max_attempts', 5),
            'otp_resend_seconds' => $settings->getInt('otp_resend_seconds', 60),
            'otp_length' => $settings->getInt('otp_length', 6),
            'customer_session_ttl_minutes' => $settings->getInt('customer_session_ttl_minutes', 30),
            'max_upload_files' => $settings->getInt('max_upload_files', 5),
            'max_upload_kb' => $settings->getInt('max_upload_kb', 5120),
            'sla_enabled' => $settings->getBool('sla_enabled', true),
            'sla_warning_hours' => $settings->getInt('sla_warning_hours', 8),
            'business_start' => $settings->get('business_start', '08:00'),
            'business_end' => $settings->get('business_end', '17:00'),
            'holidays' => $settings->get('holidays', []),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $canSettings = fn (): bool => auth()->user()?->can('settings.manage') ?? false;
        $canSms = fn (): bool => auth()->user()?->can('sms_settings.manage') ?? false;

        return $schema->components([
            Section::make('اطلاعات عمومی')->visible($canSettings)->schema([
                TextInput::make('company_name')->label('نام')->required()->maxLength(120),
                TextInput::make('company_tagline')->label('شعار')->maxLength(190),
                Textarea::make('company_description')->label('توضیح')->rows(3),
                TextInput::make('contact_phone')->label('تلفن')->maxLength(40),
                TextInput::make('contact_email')->label('ایمیل')->email()->maxLength(190),
                TextInput::make('contact_address')->label('نشانی')->maxLength(250),
                Textarea::make('required_documents_text')->label('مدارک موردنیاز')->rows(3),
                Textarea::make('process_guide')->label('راهنمای رسیدگی')->rows(3),
                FileUpload::make('company_logo')->label('لوگو')->image()->disk('public')->directory('branding')->visibility('public'),
            ])->columns(2),
            Section::make('پیامک')->visible($canSms)->schema([
                Toggle::make('sms_enabled')->label('ارسال پیامک‌های اطلاع‌رسانی'),
                Placeholder::make('gateway')->label('درگاه')->content(fn (): string => (string) config('sms.gateway')),
                Placeholder::make('token_state')->label('توکن درگاه')->content(fn (): string => filled(config('sms.http.token')) ? 'در محیط تنظیم شده و نمایش داده نمی‌شود.' : 'در محیط تنظیم نشده است.'),
            ]),
            Section::make('امنیت مشتری و فایل')->visible($canSettings)->schema([
                TextInput::make('otp_ttl_seconds')->label('اعتبار رمز (ثانیه)')->numeric()->required()->minValue(60)->maxValue(600),
                TextInput::make('otp_max_attempts')->label('سقف تلاش')->numeric()->required()->minValue(3)->maxValue(10),
                TextInput::make('otp_resend_seconds')->label('فاصله ارسال مجدد')->numeric()->required()->minValue(30)->maxValue(300),
                TextInput::make('otp_length')->label('طول رمز')->numeric()->required()->minValue(4)->maxValue(8),
                TextInput::make('customer_session_ttl_minutes')->label('مدت نشست مشتری')->numeric()->required()->minValue(5)->maxValue(120),
                TextInput::make('max_upload_files')->label('تعداد فایل')->numeric()->required()->minValue(1)->maxValue(10),
                TextInput::make('max_upload_kb')->label('حجم هر فایل (کیلوبایت)')->numeric()->required()->minValue(100)->maxValue(20480),
            ])->columns(2),
            Section::make('مهلت رسیدگی')->visible($canSettings)->schema([
                Toggle::make('sla_enabled')->label('محاسبه سررسید'),
                TextInput::make('sla_warning_hours')->label('هشدار نزدیک به مهلت (ساعت)')->numeric()->required()->minValue(1)->maxValue(72),
                TextInput::make('business_start')->label('شروع ساعت کاری')->required(),
                TextInput::make('business_end')->label('پایان ساعت کاری')->required(),
                TagsInput::make('holidays')->label('تعطیلات')->helperText('هر تاریخ را به‌صورت 2026-03-21 وارد کنید.'),
            ])->columns(2),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('settings-form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('ذخیره')->submit('save'),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testSms')
                ->label('پیامک آزمایشی')
                ->visible(fn (): bool => auth()->user()?->can('sms_settings.manage') ?? false)
                ->schema([
                    TextInput::make('mobile')->label('موبایل')->required(),
                ])
                ->action(function (array $data): void {
                    $mobile = MobileNumber::normalize((string) $data['mobile']);

                    if ($mobile === null) {
                        Notification::make()->title('شماره موبایل معتبر نیست.')->danger()->send();

                        return;
                    }

                    if (! app(SettingsService::class)->getBool('sms_enabled', false)) {
                        Notification::make()->title('ابتدا ارسال پیامک را فعال و ذخیره کنید.')->danger()->send();

                        return;
                    }

                    app(SmsService::class)->sendImmediately($mobile, 'request_submitted', [
                        'company_name' => (string) app(SettingsService::class)->get('company_name'),
                        'tracking_code' => 'TEST',
                    ], null, 'sms-test:'.auth()->id().':'.Str::uuid());

                    app(AuditLogService::class)->record('sms.test_sent', null, 'success', [
                        'recipient' => $mobile,
                    ], auth()->user());

                    Notification::make()->title('تلاش ارسال ثبت شد. نتیجه را در گزارش پیامک ببینید.')->success()->send();
                }),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $settings = app(SettingsService::class);
        $user = auth()->user();

        if ($user?->can('settings.manage')) {
            $logo = $state['company_logo'] ?? null;
            $state['company_logo'] = is_array($logo) ? ($logo[0] ?? null) : $logo;

            foreach ([
                'company_name', 'company_tagline', 'company_description', 'contact_phone', 'contact_email',
                'contact_address', 'required_documents_text', 'process_guide', 'company_logo',
            ] as $key) {
                $settings->set($key, $state[$key] ?? '', 'general', ! in_array($key, ['company_logo'], true));
            }

            foreach ([
                'otp_ttl_seconds', 'otp_max_attempts', 'otp_resend_seconds', 'otp_length',
                'customer_session_ttl_minutes', 'max_upload_files', 'max_upload_kb', 'sla_warning_hours',
            ] as $key) {
                $settings->set($key, (int) $state[$key], 'security', false);
            }

            $settings->set('sla_enabled', (bool) $state['sla_enabled'], 'sla', false);
            $settings->set('business_start', (string) $state['business_start'], 'sla', false);
            $settings->set('business_end', (string) $state['business_end'], 'sla', false);
            $settings->set('holidays', array_values($state['holidays'] ?? []), 'sla', false);
        }

        if ($user?->can('sms_settings.manage')) {
            $settings->set('sms_enabled', (bool) ($state['sms_enabled'] ?? false), 'sms', false);
        }

        app(AuditLogService::class)->record('settings.updated', null, 'success', [
            'sms_enabled' => (bool) ($state['sms_enabled'] ?? false),
        ], $user);

        Notification::make()->title('تنظیمات ذخیره شد.')->success()->send();
    }
}
