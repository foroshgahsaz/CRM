<?php

namespace App\Filament\Pages;

use App\Services\UserBulkImportResult;
use App\Services\UserBulkImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class BulkImportUsers extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $navigationLabel = 'ورود گروهی کاربران';

    protected static ?string $title = 'ورود گروهی کاربران';

    protected static string|UnitEnum|null $navigationGroup = 'سازمان';

    protected static ?int $navigationSort = 2;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('users.manage') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'is_active' => true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('راهنما')->schema([
                \Filament\Forms\Components\Placeholder::make('help')
                    ->label('')
                    ->content('ستون‌های فایل: «نام و نام خانوادگی»، «شماره موبایل»، «دسترسی» (مثلاً نماینده). می‌توانید فایل Excel (xlsx) یا CSV بارگذاری کنید. شماره‌ها می‌توانند با ارقام فارسی باشند.'),
            ]),
            Section::make('فایل و حساب')->schema([
                FileUpload::make('spreadsheet')
                    ->label('فایل Excel یا CSV')
                    ->disk('local')
                    ->directory('imports/users')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(4096)
                    ->required(),
                TextInput::make('password')
                    ->label('رمز عبور اولیه همه کاربران')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(10)->mixedCase()->numbers()->symbols())
                    ->required()
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('تکرار رمز')
                    ->password()
                    ->revealable()
                    ->dehydrated(false),
                Toggle::make('is_active')
                    ->label('کاربران فعال باشند')
                    ->default(true),
            ])->columns(1),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('bulk-user-import')
                ->livewireSubmitHandler('importUsers')
                ->footer([
                    Actions::make([
                        Action::make('import')
                            ->label('ایجاد کاربران')
                            ->submit('importUsers'),
                    ]),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadSample')
                ->label('دانلود نمونه Excel (CSV)')
                ->action(fn (): StreamedResponse => $this->downloadSample()),
        ];
    }

    public function importUsers(): void
    {
        $data = $this->form->getState();
        $paths = $data['spreadsheet'] ?? [];

        if (! is_array($paths) || $paths === []) {
            Notification::make()->title('فایلی انتخاب نشده است.')->danger()->send();

            return;
        }

        $relativePath = (string) reset($paths);
        $absolutePath = Storage::disk('local')->path($relativePath);

        if (! is_file($absolutePath)) {
            Notification::make()->title('فایل روی سرور یافت نشد. دوباره بارگذاری کنید.')->danger()->send();

            return;
        }

        $actor = auth()->user();

        if ($actor === null) {
            return;
        }

        try {
            $result = app(UserBulkImportService::class)->import(
                $absolutePath,
                (string) $data['password'],
                (bool) ($data['is_active'] ?? true),
                $actor,
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('خطا در خواندن فایل')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->notifyResult($result);
        Storage::disk('local')->delete($relativePath);
        $this->form->fill(['is_active' => $data['is_active'] ?? true]);
    }

    private function notifyResult(UserBulkImportResult $result): void
    {
        $body = 'تعداد ایجادشده: '.$result->created;

        if ($result->skipped !== []) {
            $body .= "\nرد شده: ".count($result->skipped);
        }

        if ($result->failures !== []) {
            $body .= "\nخطا: ".count($result->failures);
        }

        $details = collect($result->failures)
            ->merge($result->skipped)
            ->take(8)
            ->map(fn (array $item): string => 'ردیف '.$item['row'].': '.$item['message'])
            ->implode("\n");

        if ($details !== '') {
            $body .= "\n\n".$details;
        }

        $notification = Notification::make()
            ->title($result->created > 0 ? 'ورود گروهی انجام شد' : 'ورود گروهی بدون کاربر جدید')
            ->body($body);

        if ($result->failures !== [] && $result->created === 0) {
            $notification->danger();
        } elseif ($result->hasProblems()) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
    }

    private function downloadSample(): StreamedResponse
    {
        $filename = 'sample-bulk-users.csv';

        return response()->streamDownload(function (): void {
            echo "\xEF\xBB\xBF";
            echo "نام و نام خانوادگی,شماره موبایل,دسترسی\n";
            echo "محمد خلیلی,09141495001,نماینده\n";
            echo "مجید حیدرزاده,09151018951,نماینده\n";
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}