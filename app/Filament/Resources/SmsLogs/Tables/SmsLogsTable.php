<?php

namespace App\Filament\Resources\SmsLogs\Tables;

use App\Enums\SmsStatus;
use App\Support\JalaliDate;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SmsLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('recipient')->label('گیرنده')->searchable(),
                TextColumn::make('event_key')->label('رویداد')->searchable(),
                TextColumn::make('serviceRequest.tracking_code')->label('پرونده')->placeholder('—'),
                TextColumn::make('status')->label('وضعیت')->formatStateUsing(fn (SmsStatus $state): string => $state->label())->badge(),
                TextColumn::make('provider')->label('درگاه'),
                TextColumn::make('error_code')->label('کد خطا')->placeholder('—'),
                TextColumn::make('error_message')->label('خطا')->limit(80)->placeholder('—'),
                TextColumn::make('attempt')->label('تلاش'),
                TextColumn::make('sent_at')->label('ارسال')->formatStateUsing(fn ($state): string => JalaliDate::format($state)),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(collect(SmsStatus::cases())->mapWithKeys(fn (SmsStatus $status) => [$status->value => $status->label()])),
                SelectFilter::make('event_key')->label('رویداد')->options([
                    'otp' => 'otp',
                    'request_submitted' => 'request_submitted',
                    'customer_info_requested' => 'customer_info_requested',
                    'request_closed' => 'request_closed',
                ]),
            ]);
    }
}
