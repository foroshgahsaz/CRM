<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\MessageTemplate;
use App\Support\SmsIrPattern;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MessageTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('event_key')->label('رویداد')->disabled()->dehydrated(false),
            TextInput::make('title')->label('عنوان')->required()->maxLength(120),
            Placeholder::make('variables')->label('متغیرهای مجاز')->content(function (?MessageTemplate $record): string {
                $variables = $record?->allowed_variables ?? [];

                return $variables === [] ? '—' : implode(' ', array_map(fn (string $variable): string => '{'.$variable.'}', $variables));
            }),
            TextInput::make('sms_ir_template_id')
                ->label('آی‌دی الگو در SMS.ir')
                ->numeric()
                ->minValue(1)
                ->helperText('همان شناسه‌ای که در پنل sms.ir برای الگوی Verify می‌سازید. اگر پر شود، ارسال از API verify با پارامترهای زیر انجام می‌شود.'),
            KeyValue::make('sms_ir_parameter_map')
                ->label('نگاشت پارامتر الگو')
                ->keyLabel('متغیر در سامانه')
                ->valueLabel('نام پارامتر در sms.ir')
                ->helperText('مثلاً tracking_code → CODE یا otp_code → CODE. خالی بماند از پیش‌فرض رویداد استفاده می‌شود.')
                ->default(fn (?MessageTemplate $record): array => $record
                    ? SmsIrPattern::parameterMap($record)
                    : []),
            Textarea::make('body')->label('متن (برای ارسال bulk یا پیش‌نمایش)')->required()->rows(5)->rules([
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
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }
}
