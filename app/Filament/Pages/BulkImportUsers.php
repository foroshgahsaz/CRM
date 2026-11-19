<?php

namespace App\Filament\Pages;

use App\Services\UserBulkImportResult;
use App\Services\UserBulkImportService;
use App\Support\PermissionCatalog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
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
        Storage::disk('local')->makeDirectory('imports/users');

        $this->form->fill([
            'is_active' => true,
            'default_role' => 'representative',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('راهنما')->schema([
                \Filament\Forms\Components\Placeholder::make('help')
                    ->label('')
                    ->content('ستون‌های فایل: «نام و نام خانوادگی»، «شماره موبایل»، «دسترسی» (اختیاری — اگر خالی بود نقش پیش‌فرض زیر اعمال می‌شود). ترتیب ستون‌ها مهم نیست. Excel (xlsx) یا CSV؛ موبایل با ارقام فارسی یا بدون صفر اول هم قبول است.'),
            ]),
            Section::make('فایل و حساب')->schema([
                FileUpload::make('spreadsheet')
                    ->label('فایل Excel یا CSV')
                    ->disk('local')
                    ->directory('imports/users')
                    ->visibility('private')
                    ->multiple(false)
                    ->maxSize(5120)
                    ->required()
                    ->helperText('پس از انتخاب فایل، تا اتمام نوار پیشرفت صبر کنید، بعد «ایجاد کاربران» را بزنید.'),
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
                Select::make('default_role')
                    ->label('نقش پیش‌فرض (اگر ستون دسترسی خالی بود)')
                    ->options(fn (): array => collect(PermissionCatalog::roles())->except(['super_admin'])->all())
                    ->default('representative')
                    ->required(),
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
        try {
            $data = $this->form->getState();
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->reportImportFailure($exception, 'اعتبارسنجی فرم');

            return;
        }

        try {
            $this->runImport($data);
        } catch (Throwable $exception) {
            $this->reportImportFailure($exception, 'ورود گروهی');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function runImport(array $data): void
    {
        $relativePath = $this->extractSpreadsheetRelativePath($data['spreadsheet'] ?? null)
            ?? $this->extractSpreadsheetRelativePath($this->data['spreadsheet'] ?? null);

        if ($relativePath === null) {
            Notification::make()
                ->title('فایلی انتخاب نشده است.')
                ->body('اگر فایل را تازه بارگذاری کردید، چند ثانیه صبر کنید تا آپلود کامل شود و دوباره تلاش کنید.')
                ->danger()
                ->send();

            return;
        }

        $absolutePath = $this->resolveSpreadsheetAbsolutePath($relativePath);

        if ($absolutePath === null) {
            Notification::make()->title('فایل روی سرور یافت نشد. دوباره بارگذاری کنید.')->danger()->send();

            return;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (! in_array($extension, ['csv', 'txt', 'xlsx'], true)) {
            Notification::make()
                ->title('فرمت فایل نامعتبر است.')
                ->body('فقط فایل‌های csv یا xlsx قابل قبول هستند.')
                ->danger()
                ->send();

            return;
        }

        $actor = auth()->user();

        if ($actor === null) {
            return;
        }

        $result = app(UserBulkImportService::class)->import(
            $absolutePath,
            (string) $data['password'],
            (bool) ($data['is_active'] ?? true),
            $actor,
            (string) ($data['default_role'] ?? 'representative'),
        );

        $this->notifyResult($result);

        if (Storage::disk('local')->exists($relativePath)) {
            Storage::disk('local')->delete($relativePath);
        }

        $this->form->fill([
            'is_active' => $data['is_active'] ?? true,
            'default_role' => $data['default_role'] ?? 'representative',
            'spreadsheet' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);
    }

    private function reportImportFailure(Throwable $exception, string $context): void
    {
        Log::error('bulk_user_import.failed', [
            'context' => $context,
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ]);

        Notification::make()
            ->title('خطا در '.$context)
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }

    private function extractSpreadsheetRelativePath(mixed $value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (! is_array($value)) {
            return null;
        }

        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                return trim($item);
            }
        }

        return null;
    }

    private function resolveSpreadsheetAbsolutePath(string $relativePath): ?string
    {
        $disk = Storage::disk('local');
        $candidates = array_unique([
            $relativePath,
            'imports/users/'.$relativePath,
            'livewire-tmp/'.$relativePath,
        ]);

        foreach ($candidates as $candidate) {
            if ($disk->exists($candidate)) {
                return $disk->path($candidate);
            }
        }

        return null;
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