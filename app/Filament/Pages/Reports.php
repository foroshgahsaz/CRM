<?php

namespace App\Filament\Pages;

use App\Models\OrganizationalUnit;
use App\Models\RequestType;
use App\Services\AuditLogService;
use App\Services\ReportService;
use App\Support\JalaliDate;
use BackedEnum;
use Filament\Actions\Action;
use App\Filament\Forms\Components\JalaliDateInput;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'گزارش‌ها';

    protected static ?string $title = 'گزارش عملکرد';

    protected static string|UnitEnum|null $navigationGroup = 'نظارت';

    protected static ?int $navigationSort = 10;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, mixed>
     */
    public array $report = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('reports.view') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'from_jalali' => JalaliDate::formatDate(now()->subDays(30)),
            'to_jalali' => JalaliDate::formatDate(now()),
        ]);
        $this->apply();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            JalaliDateInput::make('from_jalali')->label('از تاریخ')->required()->minJalali('1390/01/01'),
            JalaliDateInput::make('to_jalali')->label('تا تاریخ')->required()->maxToday(),
            Select::make('request_type_id')->label('نوع درخواست')->options(fn () => RequestType::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
            Select::make('unit_id')->label('واحد')->options(fn () => OrganizationalUnit::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
        ])->columns(2);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('report-filters')
                ->livewireSubmitHandler('apply')
                ->footer([
                    Actions::make([
                        Action::make('apply')->label('نمایش گزارش')->submit('apply'),
                    ]),
                ]),
            View::make('filament.pages.report-summary')->viewData(fn (): array => ['report' => $this->report]),
        ]);
    }

    public function apply(): void
    {
        $state = $this->form->getState();
        $from = JalaliDate::toGregorianDate((string) ($state['from_jalali'] ?? ''));
        $to = JalaliDate::toGregorianDate((string) ($state['to_jalali'] ?? ''));

        if ($from === null || $to === null || $from > $to) {
            $this->report = ['error' => 'بازه تاریخ معتبر نیست.'];

            return;
        }

        $this->report = app(ReportService::class)->summary(
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
            filled($state['request_type_id'] ?? null) ? (int) $state['request_type_id'] : null,
            filled($state['unit_id'] ?? null) ? (int) $state['unit_id'] : null,
        );
        $this->report['from'] = $from;
        $this->report['to'] = $to;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('خروجی CSV')
                ->visible(fn (): bool => auth()->user()?->can('requests.export') ?? false)
                ->action(function (): StreamedResponse {
                    $this->apply();
                    app(AuditLogService::class)->record('report.exported', null, 'success', [
                        'from' => $this->report['from'] ?? null,
                        'to' => $this->report['to'] ?? null,
                    ], auth()->user());

                    return response()->streamDownload(function (): void {
                        $handle = fopen('php://output', 'w');
                        fwrite($handle, "\xEF\xBB\xBF");
                        fputcsv($handle, ['شاخص', 'مقدار']);

                        foreach ($this->flatRows() as $row) {
                            fputcsv($handle, $row);
                        }

                        fclose($handle);
                    }, 'crm-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
                }),
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function flatRows(): array
    {
        $rows = [];

        foreach ([
            'submitted_count' => 'تعداد ثبت‌شده در بازه',
            'unassigned_count' => 'تخصیص‌نیافته در بازه',
            'overdue_count' => 'معوق در بازه',
            'closed_count' => 'بسته‌شده در بازه',
            'rejection_count' => 'بازگشت برای اصلاح',
            'avg_assignments' => 'میانگین ارجاع هر پرونده',
            'avg_hours_to_first_view' => 'میانگین ساعت تا اولین مشاهده مسئول',
            'avg_hours_to_start' => 'میانگین ساعت تا شروع رسیدگی',
            'avg_expert_hours' => 'میانگین ساعت کارشناسی',
            'avg_hours_to_approval' => 'میانگین ساعت تا تأیید',
            'avg_handling_hours' => 'میانگین ساعت رسیدگی از ثبت تا بستن',
        ] as $key => $label) {
            $rows[] = [$label, (string) ($this->report[$key] ?? '—')];
        }

        return $rows;
    }
}
