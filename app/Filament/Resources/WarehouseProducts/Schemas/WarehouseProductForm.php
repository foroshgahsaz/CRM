<?php

namespace App\Filament\Resources\WarehouseProducts\Schemas;

use App\Models\Warehouse;
use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class WarehouseProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('warehouse_id')
                ->label('انبار')
                ->options(fn (): array => Warehouse::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->required()
                ->live(),
            ...self::fields(fn (Get $get): mixed => $get('warehouse_id')),
        ]);
    }

    /**
     * @param  Closure(Get): mixed  $warehouseId
     * @return list<Component>
     */
    public static function fields(Closure $warehouseId): array
    {
        return [
            TextInput::make('name')->label('نام کالا')->required()->maxLength(160),
            TextInput::make('code')
                ->label('کد کالا')
                ->required()
                ->maxLength(80)
                ->unique(
                    table: 'warehouse_products',
                    column: 'code',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule->where('warehouse_id', $warehouseId($get)),
                )
                ->validationMessages(['unique' => 'این کد کالا در این انبار قبلاً ثبت شده است.']),
            TextInput::make('quantity')
                ->label('موجودی (عدد)')
                ->integer()
                ->minValue(0)
                ->maxValue(1000000)
                ->default(0)
                ->required(),
            Textarea::make('description')->label('توضیح')->maxLength(1000)->columnSpanFull(),
        ];
    }
}
