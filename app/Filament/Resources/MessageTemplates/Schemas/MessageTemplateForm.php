<?php

namespace App\Filament\Resources\MessageTemplates\Schemas;

use App\Models\MessageTemplate;
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
            Textarea::make('body')->label('متن')->required()->rows(5)->rules([
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
