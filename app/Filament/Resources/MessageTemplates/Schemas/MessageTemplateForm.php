<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\MessageTemplate;
use App\Support\MessageTemplateCatalog;
use App\Support\SmsIrPattern;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MessageTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        $usesSmsIr = MessageTemplateCatalog::usesSmsIrPatterns();

        return $schema->components([
            Section::make('رویداد')
                ->description('هر رویداد یک نقطهٔ ارسال پیامک در سامانه است.')
                ->schema([
                    Grid::make(['default' => 1, 'md' => 2])->schema([
                        Placeholder::make('event_label')
                            ->label('نوع ارسال')
                            ->content(fn (?MessageTemplate $record): string => $record
                                ? MessageTemplateCatalog::eventLabel($record->event_key)
                                : '—'),
                        Placeholder::make('event_key_display')
                            ->label('شناسه فنی')
                            ->content(fn (?MessageTemplate $record): string => $record?->event_key ?? '—'),
                        Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->inline(false),
                    ]),
                    Placeholder::make('variables')
                        ->label('متغیرهایی که سامانه به الگو می‌فرستد')
                        ->content(function (?MessageTemplate $record): string {
                            $variables = $record?->allowed_variables ?? [];

                            if ($variables === []) {
                                return '—';
                            }

                            return implode(' · ', array_map(
                                fn (string $variable): string => '{'.$variable.'}',
                                $variables,
                            ));
                        })
                        ->helperText('نام این متغیرها را در پنل sms.ir باید در نگاشت پارامتر با نام پارامتر الگو (مثلاً CODE) جفت کنید.')
                        ->columnSpanFull(),
                ])
                ->extraAttributes(['class' => 'crm-message-template-section']),
            Section::make($usesSmsIr ? 'الگوی SMS.ir' : 'متن پیام')
                ->description($usesSmsIr
                    ? 'متن پیامک فقط در پنل sms.ir تعریف می‌شود. در اینجا آی‌دی الگوی Verify و نام پارامترها را وارد کنید.'
                    : 'برای درگاه متنی، متن پیام با جای‌نگهدار متغیرها در همین سامانه نوشته می‌شود.')
                ->schema([
                    ...($usesSmsIr ? self::smsIrFields() : self::bulkTextFields()),
                ])
                ->extraAttributes(['class' => 'crm-message-template-section']),
        ]);
    }

    /**
     * @return list<TextInput|KeyValue|Placeholder|Toggle>
     */
    private static function smsIrFields(): array
    {
        return [
            Placeholder::make('sms_ir_body_hint')
                ->label('متن پیام')
                ->content('متن این پیامک در پنل SMS.ir (بخش الگو / Verify) مدیریت می‌شود و در این صفحه قابل ویرایش نیست.')
                ->columnSpanFull(),
            TextInput::make('sms_ir_template_id')
                ->label('آی‌دی الگو')
                ->numeric()
                ->minValue(1)
                ->required(fn (Get $get): bool => (bool) $get('is_active'))
                ->helperText('شناسهٔ عددی الگو را از پنل sms.ir کپی کنید.'),
            KeyValue::make('sms_ir_parameter_map')
                ->label('نگاشت پارامتر الگو')
                ->keyLabel('متغیر در سامانه')
                ->valueLabel('نام پارامتر در sms.ir')
                ->helperText('مثلاً tracking_code → CODE یا otp_code → CODE. اگر خالی بماند از پیش‌فرض همین رویداد استفاده می‌شود.')
                ->default(fn (?MessageTemplate $record): array => $record
                    ? SmsIrPattern::parameterMap($record)
                    : [])
                ->columnSpanFull(),
            TextInput::make('title')
                ->label('عنوان داخلی')
                ->disabled()
                ->dehydrated(true),
            Textarea::make('body')
                ->hidden()
                ->dehydrated(false),
        ];
    }

    /**
     * @return list<TextInput|Textarea|Toggle>
     */
    private static function bulkTextFields(): array
    {
        return [
            TextInput::make('title')->label('عنوان')->required()->maxLength(120),
            Textarea::make('body')->label('متن پیام')->required()->rows(5)->rules([
                fn (?MessageTemplate $record): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($record): void {
                    preg_match_all('/\{([a-z0-9_]+)\}/i', (string) $value, $matches);
                    $allowed = $record?->allowed_variables ?? [];

                    foreach ($matches[1] as $variable) {
                        if (! in_array($variable, $allowed, true)) {
                            $fail('متغیر {'.$variable.'} در فهرست مجاز این قالب نیست.');
                        }
                    }
                },
            ])->columnSpanFull(),
        ];
    }
}
