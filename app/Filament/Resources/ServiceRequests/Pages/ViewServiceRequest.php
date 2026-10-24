<?php

namespace App\Filament\Resources\ServiceRequests\Pages;

use App\Enums\AttachmentCategory;
use App\Enums\AttachmentVisibility;
use App\Exceptions\WorkflowException;
use App\Filament\Actions\DeleteRecordAction;
use App\Filament\Actions\InventoryActions;
use App\Filament\Resources\ServiceRequests\ServiceRequestResource;
use App\Models\OrganizationalUnit;
use App\Models\User;
use App\Models\WarehouseProduct;
use App\Services\AssignmentService;
use App\Services\AttachmentService;
use App\Services\AuditLogService;
use App\Services\FinalApprovalService;
use App\Services\InventoryService;
use App\Services\RequestMessageService;
use App\Services\RequestViewService;
use App\Services\SettingsService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ViewServiceRequest extends ViewRecord
{
    protected static string $resource = ServiceRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $viewer = auth()->user();

        if ($viewer) {
            app(RequestViewService::class)->record($this->getRecord(), $viewer);
            $this->record = $this->getRecord()->fresh();
            $this->fillForm();
        }
    }

    public function getTitle(): string
    {
        return 'پرونده '.$this->getRecord()->tracking_code;
    }

    public function getBreadcrumb(): string
    {
        return 'مشاهده';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('claim')
                ->label('پذیرش در واحد')
                ->visible(fn (): bool => $this->allowed('claim'))
                ->requiresConfirmation()
                ->modalDescription('با پذیرش، شما مسئول فعال این پرونده می‌شوید.')
                ->action(fn () => $this->run(
                    fn () => app(AssignmentService::class)->claim($this->getRecord(), auth()->user()),
                    'پرونده پذیرفته شد.',
                )),
            Action::make('assign')
                ->label('تخصیص')
                ->visible(fn (): bool => $this->allowed('assign'))
                ->schema($this->destinationSchema(false))
                ->action(fn (array $data) => $this->run(
                    fn () => app(AssignmentService::class)->assignInitial(
                        $this->getRecord(),
                        auth()->user(),
                        $this->nullableId($data['to_user_id'] ?? null),
                        $this->nullableId($data['to_unit_id'] ?? null),
                        $data['instructions'] ?? null,
                        $data['reason'] ?? null,
                    ),
                    'تخصیص ثبت شد.',
                )),
            Action::make('reassign')
                ->label('ارجاع مجدد')
                ->visible(fn (): bool => $this->allowed('reassign'))
                ->schema($this->destinationSchema(true))
                ->action(fn (array $data) => $this->run(
                    fn () => app(AssignmentService::class)->reassign(
                        $this->getRecord(),
                        auth()->user(),
                        $this->nullableId($data['to_user_id'] ?? null),
                        $this->nullableId($data['to_unit_id'] ?? null),
                        (string) $data['reason'],
                        $data['instructions'] ?? null,
                    ),
                    'ارجاع ثبت شد.',
                )),
            ActionGroup::make([
                Action::make('expertNote')
                    ->label('توضیح کارشناسی')
                    ->visible(fn (): bool => $this->allowed('reply'))
                    ->schema([Textarea::make('body')->label('متن')->required()->minLength(3)])
                    ->action(fn (array $data) => $this->run(
                        fn () => app(RequestMessageService::class)->addExpertNote($this->getRecord(), auth()->user(), $data['body']),
                        'توضیح کارشناسی ثبت شد.',
                    )),
                Action::make('reviewResult')
                    ->label('نتیجه بررسی')
                    ->visible(fn (): bool => $this->allowed('reply'))
                    ->schema([Textarea::make('body')->label('نتیجه')->required()->minLength(3)])
                    ->action(fn (array $data) => $this->run(
                        fn () => app(RequestMessageService::class)->addReviewResult($this->getRecord(), auth()->user(), $data['body']),
                        'نتیجه بررسی ثبت شد.',
                    )),
                Action::make('internalNote')
                    ->label('یادداشت داخلی')
                    ->visible(fn (): bool => $this->allowed('addInternalNote'))
                    ->schema([Textarea::make('body')->label('یادداشت')->required()->minLength(3)])
                    ->action(fn (array $data) => $this->run(
                        fn () => app(RequestMessageService::class)->addInternalNote($this->getRecord(), auth()->user(), $data['body']),
                        'یادداشت داخلی ثبت شد.',
                    )),
                Action::make('adminNote')
                    ->label('یادداشت مدیریتی')
                    ->visible(fn (): bool => $this->allowed('addAdminNote'))
                    ->schema([Textarea::make('body')->label('یادداشت محرمانه')->required()->minLength(3)])
                    ->action(fn (array $data) => $this->run(
                        fn () => app(RequestMessageService::class)->addAdminNote($this->getRecord(), auth()->user(), $data['body']),
                        'یادداشت مدیریتی ثبت شد.',
                    )),
                Action::make('requestInfo')
                    ->label('درخواست مدرک از مشتری')
                    ->visible(fn (): bool => $this->allowed('requestCustomerInfo'))
                    ->requiresConfirmation()
                    ->schema([Textarea::make('body')->label('پیام قابل نمایش به مشتری')->required()->minLength(5)])
                    ->action(fn (array $data) => $this->run(
                        fn () => app(RequestMessageService::class)->requestCustomerInformation($this->getRecord(), auth()->user(), $data['body']),
                        'درخواست مدرک ثبت شد.',
                    )),
            ])->label('پیام‌ها')->button(),
            InventoryActions::allocateFromRequest()
                ->visible(fn (): bool => (auth()->user()?->can('inventory.allocate') ?? false) && ! $this->getRecord()->status->isTerminal())
                ->action(function (array $data): void {
                    $product = WarehouseProduct::query()->find($data['warehouse_product_id'] ?? null);

                    if (! $product) {
                        Notification::make()->title('کالای انتخاب‌شده یافت نشد.')->danger()->send();

                        return;
                    }

                    $this->run(
                        fn () => app(InventoryService::class)->allocate($product, $this->getRecord(), auth()->user(), $data['note'] ?? null),
                        'یک عدد «'.$product->displayLabel().'» به این درخواست ارجاع شد.',
                    );
                }),
            Action::make('upload')
                ->label('پیوست داخلی')
                ->visible(fn (): bool => $this->allowed('uploadInternalAttachment'))
                ->schema([
                    FileUpload::make('attachments')
                        ->label('فایل‌ها')
                        ->multiple()
                        ->required()
                        ->storeFiles(false)
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                        ->maxSize(fn (): int => app(SettingsService::class)->getInt('max_upload_kb', 5120)),
                ])
                ->action(function (array $data): void {
                    $this->run(function () use ($data): void {
                        $record = $this->getRecord();
                        Gate::authorize('uploadInternalAttachment', $record);
                        $files = collect($data['attachments'] ?? [])->flatten()->filter(fn (mixed $file): bool => $file instanceof UploadedFile);

                        foreach ($files as $file) {
                            $attachment = app(AttachmentService::class)->store(
                                $file,
                                $record,
                                AttachmentCategory::Internal,
                                AttachmentVisibility::Internal,
                                auth()->user(),
                                null,
                                $record->current_assignment_id,
                            );
                            app(AuditLogService::class)->record('attachment.uploaded', $attachment, 'success', [
                                'request_id' => $record->id,
                                'visibility' => AttachmentVisibility::Internal->value,
                            ], auth()->user());
                        }
                    }, 'پیوست داخلی ثبت شد.');
                }),
            Action::make('complete')
                ->label('اتمام کارشناسی')
                ->color('warning')
                ->visible(fn (): bool => $this->allowed('complete'))
                ->requiresConfirmation()
                ->modalDescription('پس از تکمیل، پرونده برای تأیید نهایی می‌رود و شما دیگر نمی‌توانید آن را ویرایش کنید.')
                ->schema([Textarea::make('summary')->label('نتیجه نهایی کارشناسی')->required()->minLength(5)])
                ->action(fn (array $data) => $this->run(
                    fn () => app(AssignmentService::class)->complete($this->getRecord(), auth()->user(), $data['summary']),
                    'کارشناسی تکمیل و برای تأیید ارسال شد.',
                )),
            Action::make('approve')
                ->label('تأیید نهایی')
                ->color('success')
                ->visible(fn (): bool => $this->allowed('approve'))
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('summary')->label('نتیجه قابل نمایش به مشتری')->required()->minLength(5),
                    Toggle::make('close')->label('بستن پرونده بلافاصله پس از تأیید')->default(true)
                        ->visible(fn (): bool => auth()->user()?->can('requests.close') ?? false),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => app(FinalApprovalService::class)->approve(
                        $this->getRecord(),
                        auth()->user(),
                        $data['summary'],
                        (bool) ($data['close'] ?? false) && (auth()->user()?->can('requests.close') ?? false),
                    ),
                    'تصمیم تأیید ثبت شد.',
                )),
            Action::make('reject')
                ->label('رد تأیید')
                ->color('danger')
                ->visible(fn (): bool => $this->allowed('rejectApproval'))
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('دلیل بازگشت برای اصلاح')->required()->minLength(5),
                    Select::make('assignee_id')->label('کارشناس اصلاح')->options(fn (): array => $this->userOptions())->searchable()->required(),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => app(FinalApprovalService::class)->reject(
                        $this->getRecord(),
                        auth()->user(),
                        $data['reason'],
                        (int) $data['assignee_id'],
                    ),
                    'پرونده برای اصلاح بازگردانده شد.',
                )),
            Action::make('close')
                ->label('بستن')
                ->visible(fn (): bool => $this->allowed('close') && $this->getRecord()->status->value === 'approved')
                ->requiresConfirmation()
                ->action(fn () => $this->run(
                    fn () => app(FinalApprovalService::class)->close($this->getRecord(), auth()->user()),
                    'پرونده بسته شد.',
                )),
            Action::make('archive')
                ->label('بایگانی')
                ->visible(fn (): bool => $this->allowed('archive'))
                ->requiresConfirmation()
                ->modalDescription('بایگانی حذف نیست. پرونده فقط خواندنی می‌شود.')
                ->action(fn () => $this->run(
                    fn () => app(FinalApprovalService::class)->archive($this->getRecord(), auth()->user()),
                    'پرونده بایگانی شد.',
                )),
            Action::make('reopen')
                ->label('بازگشایی')
                ->visible(fn (): bool => $this->allowed('reopen'))
                ->requiresConfirmation()
                ->schema([
                    Textarea::make('reason')->label('دلیل بازگشایی')->required()->minLength(5),
                    Select::make('assignee_id')->label('مسئول جدید')->options(fn (): array => $this->userOptions())->searchable()->required(),
                ])
                ->action(fn (array $data) => $this->run(
                    fn () => app(FinalApprovalService::class)->reopen(
                        $this->getRecord(),
                        auth()->user(),
                        $data['reason'],
                        (int) $data['assignee_id'],
                    ),
                    'پرونده بازگشایی شد.',
                )),
            DeleteRecordAction::make()->label('حذف درخواست'),
        ];
    }

    /**
     * @return list<Component>
     */
    private function destinationSchema(bool $reasonRequired): array
    {
        return [
            Select::make('to_user_id')->label('کارشناس')->options(fn (): array => $this->userOptions())->searchable(),
            Select::make('to_unit_id')->label('واحد سازمانی')->options(fn (): array => $this->unitOptions())->searchable(),
            Textarea::make('reason')->label('دلیل')->required($reasonRequired)->minLength($reasonRequired ? 3 : 0),
            Textarea::make('instructions')->label('دستور'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function userOptions(): array
    {
        return User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, string>
     */
    private function unitOptions(): array
    {
        return OrganizationalUnit::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }

    private function allowed(string $ability): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can($ability, $this->getRecord());
    }

    private function nullableId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }

    private function run(callable $callback, string $success): void
    {
        try {
            $callback();
            $this->record = $this->getRecord()->fresh();
            $this->fillForm();
            Notification::make()->title($success)->success()->send();
        } catch (WorkflowException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
        } catch (AuthorizationException $exception) {
            Notification::make()->title($exception->getMessage() !== '' ? $exception->getMessage() : 'دسترسی لازم برای این عملیات وجود ندارد.')->danger()->send();
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first();
            Notification::make()->title(is_string($message) ? $message : 'اطلاعات ناقص است.')->danger()->send();
        }
    }
}
