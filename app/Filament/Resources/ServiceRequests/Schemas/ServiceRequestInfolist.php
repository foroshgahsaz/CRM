<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use App\Filament\Support\RequestTimeline;
use App\Models\ServiceRequest;
use App\Support\JalaliDate;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ServiceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.service-requests.header')->columnSpanFull(),
            Tabs::make('request-tabs')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('تایم‌لاین')
                        ->key('timeline')
                        ->icon('heroicon-o-clock')
                        ->schema([View::make('filament.service-requests.timeline')]),
                    Tab::make('اطلاعات')
                        ->key('info')
                        ->icon('heroicon-o-information-circle')
                        ->schema(self::infoSchema()),
                    Tab::make('ارجاع‌ها')
                        ->key('assignments')
                        ->icon('heroicon-o-arrows-right-left')
                        ->badge(fn (ServiceRequest $record): ?int => self::count($record->assignments()->count()))
                        ->schema([View::make('filament.service-requests.assignments')]),
                    Tab::make('پیام‌ها')
                        ->key('messages')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->badge(fn (ServiceRequest $record): ?int => self::count(RequestTimeline::visibleMessages($record, auth()->user())->count()))
                        ->schema([View::make('filament.service-requests.messages')]),
                    Tab::make('مدارک')
                        ->key('attachments')
                        ->icon('heroicon-o-paper-clip')
                        ->badge(fn (ServiceRequest $record): ?int => self::count($record->attachments()->count()))
                        ->schema([View::make('filament.service-requests.attachments')]),
                    Tab::make('انبار')
                        ->key('inventory')
                        ->icon('heroicon-o-archive-box')
                        ->visible(fn (ServiceRequest $record): bool => $record->inventoryAllocations()->exists())
                        ->badge(fn (ServiceRequest $record): ?int => self::count($record->inventoryAllocations()->whereNull('cancelled_at')->count()))
                        ->badgeColor('success')
                        ->schema([View::make('filament.service-requests.inventory')]),
                    Tab::make('تأیید و مشاهده')
                        ->key('oversight')
                        ->icon('heroicon-o-shield-check')
                        ->schema([View::make('filament.service-requests.oversight')]),
                ]),
        ]);
    }

    /**
     * @return list<Component>
     */
    private static function infoSchema(): array
    {
        return [
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('شرح درخواست')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('description')->hiddenLabel()->prose(),
                        TextEntry::make('customer_visible_summary')->label('نتیجهٔ قابل نمایش به مشتری')->placeholder('هنوز ثبت نشده')
                            ->visible(fn (ServiceRequest $record): bool => filled($record->customer_visible_summary)),
                    ]),
                Section::make('مشتری')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('customer.full_name')->label('نام'),
                        TextEntry::make('customer.mobile')->label('موبایل')->copyable()
                            ->visible(fn (ServiceRequest $record): bool => auth()->user()?->can('viewSensitive', $record) ?? false),
                    ])->columns(2),
                Section::make('خرید')
                    ->icon('heroicon-o-shopping-bag')
                    ->schema([
                        TextEntry::make('purchase_date')->label('تاریخ خرید')->formatStateUsing(fn ($state) => JalaliDate::formatDate($state)),
                        TextEntry::make('invoice_number')->label('فاکتور')->placeholder('—'),
                        TextEntry::make('product_name')->label('محصول')->placeholder('—'),
                        TextEntry::make('purchased_quantity')->label('تعداد خرید')->placeholder('—'),
                        TextEntry::make('affected_quantity')->label('تعداد مشکل‌دار')->placeholder('—'),
                    ])->columns(2),
                Section::make('پرونده')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        TextEntry::make('requestType.name')->label('نوع'),
                        TextEntry::make('status')->label('وضعیت')->badge()
                            ->formatStateUsing(fn ($state) => $state->label())
                            ->color(fn ($state): string => RequestTimeline::statusColor($state)),
                        TextEntry::make('priority')->label('اولویت')->formatStateUsing(fn ($state) => $state->label()),
                        TextEntry::make('submitted_at')->label('ثبت')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                        TextEntry::make('due_at')->label('سررسید')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                        TextEntry::make('closed_at')->label('بسته شدن')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                    ])->columns(2),
                Section::make('فیلدهای اختصاصی')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->visible(fn (ServiceRequest $record): bool => $record->fieldValues()->exists())
                    ->schema([
                        RepeatableEntry::make('fieldValues')->hiddenLabel()->schema([
                            TextEntry::make('label_snapshot')->label('فیلد'),
                            TextEntry::make('display')->label('مقدار')->state(fn ($record) => $record->displayValue()),
                        ])->columns(2),
                    ]),
            ]),
        ];
    }

    private static function count(int $count): ?int
    {
        return $count > 0 ? $count : null;
    }
}
