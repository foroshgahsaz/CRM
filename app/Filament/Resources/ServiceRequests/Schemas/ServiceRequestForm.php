<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use App\Enums\FieldType;
use App\Enums\RequestPriority;
use App\Models\RequestField;
use App\Services\SettingsService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ServiceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')->label('نام و نام خانوادگی')->required()->maxLength(120),
            TextInput::make('mobile')->label('موبایل')->required()->maxLength(20),
            Select::make('request_type_id')->label('نوع درخواست')
                ->relationship('requestType', 'name', fn ($query) => $query->where('is_active', true)->orderBy('sort_order'))
                ->required()->live(),
            TextInput::make('purchase_jalali')->label('تاریخ خرید شمسی')->placeholder('1404/07/27')->required(),
            TextInput::make('invoice_number')->label('شماره فاکتور')->maxLength(50),
            TextInput::make('product_name')->label('محصول')->maxLength(190),
            TextInput::make('purchased_quantity')->label('تعداد خرید')->numeric()->minValue(1),
            TextInput::make('affected_quantity')->label('تعداد مشکل‌دار')->numeric()->minValue(0),
            Select::make('priority')->label('اولویت')->options(collect(RequestPriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => $priority->label()]))->default(RequestPriority::Normal->value)->required(),
            Textarea::make('description')->label('شرح')->required()->minLength(10)->columnSpanFull(),
            FileUpload::make('attachments')
                ->label('مدارک')
                ->multiple()
                ->storeFiles(false)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->maxSize(fn (): int => app(SettingsService::class)->getInt('max_upload_kb', 5120))
                ->maxFiles(fn (): int => app(SettingsService::class)->getInt('max_upload_files', 5))
                ->helperText('JPG، PNG، WEBP یا PDF. فایل روی دیسک خصوصی ذخیره می‌شود.')
                ->columnSpanFull(),
            Section::make('فیلدهای اختصاصی')->schema(function (Get $get): array {
                $typeId = $get('request_type_id');

                if (! $typeId) {
                    return [];
                }

                return RequestField::query()
                    ->where('request_type_id', $typeId)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get()
                    ->map(function (RequestField $field) {
                        $name = 'fields.'.$field->field_key;
                        $input = match ($field->field_type) {
                            FieldType::Textarea => Textarea::make($name),
                            FieldType::Select => Select::make($name)->options(collect($field->options ?? [])->mapWithKeys(fn ($option) => [$option => $option])),
                            default => TextInput::make($name),
                        };

                        return $input->label($field->label)->required($field->is_required);
                    })
                    ->all();
            })->columnSpanFull(),
        ]);
    }
}
