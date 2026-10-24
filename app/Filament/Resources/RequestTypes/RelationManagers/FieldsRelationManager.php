<?php

namespace App\Filament\Resources\RequestTypes\RelationManagers;

use App\Enums\FieldType;
use App\Filament\Actions\DeleteRecordAction;
use App\Services\DynamicFieldService;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class FieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'fields';

    protected static ?string $title = 'فیلدهای اختصاصی';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('برچسب')->required()->maxLength(120),
            TextInput::make('field_key')->label('کلید')->required()->alphaDash()->maxLength(50)
                ->disabled(fn (?Model $record): bool => $record !== null && $record->values()->exists())
                ->unique(table: 'request_fields', column: 'field_key', ignoreRecord: true, modifyRuleUsing: function ($rule) {
                    return $rule->where('request_type_id', $this->getOwnerRecord()->getKey());
                }),
            Select::make('field_type')->label('نوع')->options(collect(FieldType::cases())->mapWithKeys(fn (FieldType $type) => [$type->value => $type->label()]))->required()
                ->disabled(fn (?Model $record): bool => $record !== null && $record->values()->exists()),
            TagsInput::make('options')->label('گزینه‌ها')->helperText('برای فیلد انتخابی، هر گزینه را جدا وارد کنید.'),
            TagsInput::make('validation_rules')->label('قواعد اضافه')->helperText('فقط string، integer، numeric، boolean، date، email و min:n یا max:n.')
                ->rules([
                    fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                        $service = app(DynamicFieldService::class);

                        foreach ((array) $value as $rule) {
                            if (! is_string($rule) || ! $service->isAllowedStoredRule($rule)) {
                                $fail('قاعده «'.$rule.'» در فهرست مجاز نیست.');
                            }
                        }
                    },
                ]),
            Toggle::make('is_required')->label('الزامی')->default(false),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort_order')->label('ترتیب')->numeric()->default(0)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')->label('ترتیب'),
                TextColumn::make('label')->label('برچسب'),
                TextColumn::make('field_key')->label('کلید'),
                TextColumn::make('field_type')->label('نوع')->formatStateUsing(fn (FieldType $state): string => $state->label()),
                IconColumn::make('is_required')->label('الزامی')->boolean(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteRecordAction::make(),
            ]);
    }
}
