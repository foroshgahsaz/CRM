<?php

namespace App\Filament\Resources\ServiceRequests\Schemas;

use App\Enums\MessageVisibility;
use App\Models\InventoryAllocation;
use App\Models\ServiceRequest;
use App\Support\JalaliDate;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('پرونده')->schema([
                TextEntry::make('tracking_code')->label('کد رهگیری'),
                TextEntry::make('status')->label('وضعیت')->formatStateUsing(fn ($state) => $state->label()),
                TextEntry::make('priority')->label('اولویت')->formatStateUsing(fn ($state) => $state->label()),
                TextEntry::make('requestType.name')->label('نوع'),
                TextEntry::make('submitted_at')->label('ثبت')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                TextEntry::make('due_at')->label('سررسید')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                TextEntry::make('currentOwner.name')->label('مسئول فعال')->placeholder('ندارد'),
                TextEntry::make('currentUnit.name')->label('واحد')->placeholder('ندارد'),
                TextEntry::make('description')->label('شرح')->columnSpanFull(),
            ])->columns(2),
            Section::make('مشتری')->schema([
                TextEntry::make('customer.full_name')->label('نام'),
                TextEntry::make('customer.mobile')->label('موبایل')->visible(fn (ServiceRequest $record): bool => auth()->user()?->can('viewSensitive', $record) ?? false),
            ])->columns(2),
            Section::make('خرید')->schema([
                TextEntry::make('purchase_date')->label('تاریخ خرید')->formatStateUsing(fn ($state) => JalaliDate::formatDate($state)),
                TextEntry::make('invoice_number')->label('فاکتور')->placeholder('—'),
                TextEntry::make('product_name')->label('محصول')->placeholder('—'),
                TextEntry::make('purchased_quantity')->label('تعداد خرید')->placeholder('—'),
                TextEntry::make('affected_quantity')->label('تعداد مشکل‌دار')->placeholder('—'),
            ])->columns(2),
            Section::make('کالای ارجاع‌شده از انبار')
                ->visible(fn (ServiceRequest $record): bool => $record->inventoryAllocations()->exists())
                ->schema([
                    RepeatableEntry::make('inventoryAllocations')->schema([
                        TextEntry::make('product_name_snapshot')->label('کالا'),
                        TextEntry::make('product_code_snapshot')->label('کد کالا'),
                        TextEntry::make('warehouse_name_snapshot')->label('انبار'),
                        TextEntry::make('allocator.name')->label('ارجاع‌دهنده')->placeholder('—'),
                        TextEntry::make('created_at')->label('زمان ارجاع')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                        TextEntry::make('state')->label('وضعیت')
                            ->state(fn (InventoryAllocation $record): string => $record->isActive()
                                ? 'فعال (۱ عدد)'
                                : 'لغو شده: '.$record->cancel_reason)
                            ->badge()
                            ->color(fn (InventoryAllocation $record): string => $record->isActive() ? 'success' : 'gray'),
                        TextEntry::make('note')->label('توضیح')->placeholder('—')->columnSpanFull(),
                    ])->columns(2),
                ]),
            Section::make('فیلدهای اختصاصی')->schema([
                RepeatableEntry::make('fieldValues')->schema([
                    TextEntry::make('label_snapshot')->label('فیلد'),
                    TextEntry::make('display')->label('مقدار')->state(fn ($record) => $record->displayValue()),
                ])->columns(2),
            ]),
            Section::make('پیام‌ها')->schema([
                RepeatableEntry::make('messages')
                    ->getStateUsing(fn (ServiceRequest $record) => $record->messages->filter(function ($message) use ($record) {
                        if ($message->visibility === MessageVisibility::AdminOnly) {
                            return auth()->user()?->can('viewAdminNotes', $record) ?? false;
                        }

                        return true;
                    })->values())
                    ->schema([
                        TextEntry::make('message_type')->label('نوع')->formatStateUsing(fn ($state) => $state->label()),
                        TextEntry::make('visibility')->label('نمایش')->formatStateUsing(fn ($state) => $state->label()),
                        TextEntry::make('body')->label('متن')->columnSpanFull(),
                        TextEntry::make('created_at')->label('زمان')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                    ])->columns(2),
            ]),
            Section::make('ارجاع‌ها')->schema([
                RepeatableEntry::make('assignments')->schema([
                    TextEntry::make('assignment_type')->label('نوع')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('status')->label('وضعیت')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('toUser.name')->label('مقصد')->placeholder('واحد'),
                    TextEntry::make('toUnit.name')->label('واحد')->placeholder('—'),
                    TextEntry::make('reason')->label('دلیل')->placeholder('—'),
                    TextEntry::make('first_viewed_at')->label('اولین مشاهده')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                    TextEntry::make('completed_at')->label('تکمیل')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                    TextEntry::make('completion_summary')->label('نتیجه')->placeholder('—')->columnSpanFull(),
                ])->columns(2),
            ]),
            Section::make('تاریخچه وضعیت')->schema([
                RepeatableEntry::make('statusHistories')->schema([
                    TextEntry::make('from_status')->label('از')->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                    TextEntry::make('to_status')->label('به')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('reason')->label('دلیل')->placeholder('—'),
                    TextEntry::make('changer.name')->label('عامل')->placeholder('سامانه'),
                    TextEntry::make('created_at')->label('زمان')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                ])->columns(2),
            ]),
            Section::make('مشاهده‌ها')->schema([
                RepeatableEntry::make('views')->schema([
                    TextEntry::make('user.name')->label('کاربر'),
                    TextEntry::make('kind')->label('نوع')->formatStateUsing(fn (string $state) => $state === 'assignee' ? 'مسئول مأموریت' : 'کارشناس یا ناظر'),
                    TextEntry::make('first_viewed_at')->label('اولین')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                    TextEntry::make('view_count')->label('دفعات'),
                ])->columns(2),
            ]),
            Section::make('تأیید و رد')->schema([
                RepeatableEntry::make('approvalDecisions')->schema([
                    TextEntry::make('decision')->label('تصمیم')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('decider.name')->label('مدیر')->placeholder('—'),
                    TextEntry::make('reason')->label('شرح')->columnSpanFull(),
                    TextEntry::make('created_at')->label('زمان')->formatStateUsing(fn ($state) => JalaliDate::format($state)),
                ]),
            ]),
            Section::make('مدارک')->schema([
                RepeatableEntry::make('attachments')->schema([
                    TextEntry::make('original_name')->label('فایل'),
                    TextEntry::make('category')->label('دسته')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('visibility')->label('نمایش')->formatStateUsing(fn ($state) => $state->label()),
                    TextEntry::make('link')->label('دانلود')->state(fn ($record) => 'دانلود')->url(fn ($record) => route('staff.attachments.show', $record))->openUrlInNewTab(),
                ])->columns(2),
            ]),
        ]);
    }
}
