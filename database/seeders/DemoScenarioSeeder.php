<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Services\AssignmentService;
use App\Services\FinalApprovalService;
use App\Services\RequestMessageService;
use App\Services\RequestSubmissionService;
use App\Services\RequestViewService;
use App\Support\JalaliDate;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoScenarioSeeder extends Seeder
{
    private const DEMO_PASSWORD = 'demo1234';

    /** @var array<int, array{email: string, name: string, role: string, unit_code: ?string, login: string}> */
    private array $staffBlueprint = [
        ['login' => 'manager', 'email' => 'manager@demo.local', 'name' => 'مدیر عملیات', 'role' => 'system_administrator', 'unit_code' => null],
        ['login' => 'dispatch', 'email' => 'dispatch@demo.local', 'name' => 'کارشناس توزیع', 'role' => 'system_administrator', 'unit_code' => 'DEMO-SALES'],
        ['login' => 'expert.sales', 'email' => 'expert.sales@demo.local', 'name' => 'کارشناس فروش (سارا)', 'role' => 'expert', 'unit_code' => 'DEMO-SALES'],
        ['login' => 'expert.qc', 'email' => 'expert.qc@demo.local', 'name' => 'کارشناس QC (رضا)', 'role' => 'expert', 'unit_code' => 'DEMO-QC'],
        ['login' => 'expert.warehouse', 'email' => 'expert.warehouse@demo.local', 'name' => 'کارشناس انبار (مهدی)', 'role' => 'expert', 'unit_code' => 'DEMO-WH'],
        ['login' => 'lead.warehouse', 'email' => 'lead.warehouse@demo.local', 'name' => 'سرپرست انبار (نیلوفر)', 'role' => 'system_administrator', 'unit_code' => 'DEMO-WH'],
        ['login' => 'expert.support', 'email' => 'expert.support@demo.local', 'name' => 'پشتیبانی (پریسا)', 'role' => 'expert', 'unit_code' => 'DEMO-SUPPORT'],
        ['login' => 'auditor', 'email' => 'auditor@demo.local', 'name' => 'ممیز (کامیل)', 'role' => 'auditor', 'unit_code' => null],
    ];

    public function run(): void
    {
        if (! Schema::hasTable('service_requests')) {
            $this->command?->error('ابتدا migrate را اجرا کنید.');

            return;
        }

        $this->call([
            RoleAndPermissionSeeder::class,
            RequestTypeSeeder::class,
            AdminUserSeeder::class,
        ]);

        $this->purgePreviousDemo();

        $units = $this->seedUnits();
        $users = $this->seedStaff($units);

        $manager = $users['manager@demo.local'];
        $dispatch = $users['dispatch@demo.local'];
        $sales = $users['expert.sales@demo.local'];
        $qc = $users['expert.qc@demo.local'];
        $warehouse = $users['expert.warehouse@demo.local'];
        $leadWh = $users['lead.warehouse@demo.local'];
        $support = $users['expert.support@demo.local'];

        $submit = app(RequestSubmissionService::class);
        $assign = app(AssignmentService::class);
        $views = app(RequestViewService::class);
        $messages = app(RequestMessageService::class);
        $approval = app(FinalApprovalService::class);

        $types = [
            'breakage' => \App\Models\RequestType::query()->where('slug', 'breakage')->firstOrFail(),
            'shortage' => \App\Models\RequestType::query()->where('slug', 'shortage')->firstOrFail(),
            'mismatch' => \App\Models\RequestType::query()->where('slug', 'mismatch')->firstOrFail(),
        ];

        $purchase = now()->subDays(5)->toDateString();

        // 1 — فقط ثبت، بدون ارجاع
        $submit->submitPublic($this->payload('علی رضایی', $types['breakage']->id, $purchase, 'شکستگی لبه لیوان'), $this->demoFiles(), '09121000001', 'demo-seed-01');

        // 2 — ارجاع اولیه، هنوز بازدید نشده
        $r2 = $submit->submitPublic($this->payload('مریم کاظمی', $types['shortage']->id, $purchase, 'دو قلم از سفارش نبود.'), $this->demoFiles(), '09121000002', 'demo-seed-02');
        $assign->assignInitial($r2, $dispatch, $sales->id, $units['DEMO-SALES']->id, 'بررسی فاکتور فروش', 'تخصیص اولیه');

        // 3 — در حال رسیدگی + یادداشت
        $r3 = $submit->submitPublic($this->payload('حسین مرادی', $types['mismatch']->id, $purchase, 'رنگ کالا با سفارش فرق داشت.'), $this->demoFiles(), '09121000003', 'demo-seed-03');
        $assign->assignInitial($r3, $manager, $qc->id, $units['DEMO-QC']->id, 'کنترل مغایرت', 'QC');
        $views->record($r3->refresh(), $qc);
        $messages->addExpertNote($r3->refresh(), $qc, 'تصاویر با فاکتور مقایسه شد؛ مغایرت جزئی است.');
        $messages->addInternalNote($r3->refresh(), $qc, 'نیاز به تأیید سرپرست فروش');

        // 4 — منتظر مشتری
        $r4 = $submit->submitPublic($this->payload('زهرا موسوی', $types['breakage']->id, $purchase, 'بسته‌بندی آسیب دیده.'), $this->demoFiles(), '09121000004', 'demo-seed-04');
        $assign->assignInitial($r4, $dispatch, $support->id, $units['DEMO-SUPPORT']->id, 'پیگیری مدارک', 'پشتیبانی');
        $views->record($r4->refresh(), $support);
        $messages->requestCustomerInformation($r4->refresh(), $support, 'لطفاً تصویر بسته‌بندی و فاکتور را بارگذاری کنید.');

        // 5 — بعد از مدرک مشتری
        $r5 = $submit->submitPublic($this->payload('امیرحسین جعفری', $types['breakage']->id, $purchase, 'شکستگی در حمل'), $this->demoFiles(), '09121000005', 'demo-seed-05');
        $assign->assignInitial($r5, $manager, $warehouse->id, $units['DEMO-WH']->id, 'بررسی حمل', 'انبار');
        $views->record($r5->refresh(), $warehouse);
        $messages->requestCustomerInformation($r5->refresh(), $warehouse, 'عکس کارتن خارجی را بفرستید.');
        $submit->receiveSupplement($r5->refresh(), [UploadedFile::fake()->image('carton.jpg')]);
        $messages->addExpertNote($r5->refresh(), $warehouse, 'آسیب حمل تأیید شد.');

        // 6 — منتظر تأیید نهایی
        $r6 = $submit->submitPublic($this->payload('سمیه احمدی', $types['shortage']->id, $purchase, 'کسری ۱ عددی'), $this->demoFiles(), '09121000006', 'demo-seed-06');
        $assign->assignInitial($r6, $dispatch, $sales->id, null, 'رسیدگی', 'فروش');
        $views->record($r6->refresh(), $sales);
        $messages->addReviewResult($r6->refresh(), $sales, 'کسری تأیید؛ پیشنهاد ارسال کالا.');
        $assign->complete($r6->refresh(), $sales, 'کارشناسی: کسری ۱ عدد تأیید شد.');

        // 7 — بسته‌شده
        $r7 = $submit->submitPublic($this->payload('پوریا نادری', $types['mismatch']->id, $purchase, 'مدل اشتباه ارسال شده'), $this->demoFiles(), '09121000007', 'demo-seed-07');
        $assign->assignInitial($r7, $manager, $qc->id, null, null, 'QC');
        $views->record($r7->refresh(), $qc);
        $assign->complete($r7->refresh(), $qc, 'تأیید مغایرت؛ تعویض کالا.');
        $approval->approve($r7->refresh(), $manager, 'کالای صحیح برای مشتری ارسال می‌شود.', true);

        // 8 — زنجیره ارجاع: فروش → واحد انبار → claim
        $r8 = $submit->submitPublic($this->payload('نگار صفری', $types['breakage']->id, $purchase, 'شکستگی چندتایی'), $this->demoFiles(), '09121000008', 'demo-seed-08');
        $assign->assignInitial($r8, $dispatch, $sales->id, $units['DEMO-SALES']->id, 'اولیه', 'فروش');
        $views->record($r8->refresh(), $sales);
        $messages->addExpertNote($r8->refresh(), $sales, 'ارجاع به انبار برای کنترل موجودی.');
        $assign->reassign($r8->refresh(), $sales, null, $units['DEMO-WH']->id, 'نیاز به بررسی انبار', 'موجودی');
        $assign->claim($r8->refresh(), $leadWh);
        $views->record($r8->refresh(), $leadWh);
        $messages->addExpertNote($r8->refresh(), $leadWh, 'پس از claim: موجودی جایگزین موجود است.');
        $messages->addAdminNote($r8->refresh(), $manager, 'یادداشت مدیر روی پروندهٔ چندارجاعی');

        // 9 — چند ارجاع بین کارشناسان
        $r9 = $submit->submitPublic($this->payload('کاوه شریفی', $types['shortage']->id, $purchase, 'کسری و احتمال شکستگی'), $this->demoFiles(), '09121000009', 'demo-seed-09');
        $assign->assignInitial($r9, $manager, $sales->id, null, 'فروش', 'شروع');
        $views->record($r9->refresh(), $sales);
        $assign->reassign($r9->refresh(), $sales, $qc->id, $units['DEMO-QC']->id, 'ارجاع QC', 'بررسی فنی');
        $views->record($r9->refresh(), $qc);
        $messages->addExpertNote($r9->refresh(), $qc, 'QC: بسته‌بندی سالم؛ مشکل از فروش.');
        $assign->reassign($r9->refresh(), $qc, $warehouse->id, $units['DEMO-WH']->id, 'ارجاع انبار', 'حمل');
        $views->record($r9->refresh(), $warehouse);
        $messages->addInternalNote($r9->refresh(), $warehouse, 'انبار: احتمال آسیب در بارگیری');

        // 10 — آرشیو
        $r10 = $submit->submitPublic($this->payload('لیلا باقری', $types['breakage']->id, $purchase, 'پروندهٔ نمونه بایگانی'), $this->demoFiles(), '09121000010', 'demo-seed-10');
        $assign->assignInitial($r10, $dispatch, $support->id, null, null, 'پشتیبانی');
        $views->record($r10->refresh(), $support);
        $assign->complete($r10->refresh(), $support, 'مورد بسته شد.');
        $closed = $approval->approve($r10->refresh(), $manager, 'رسیدگی انجام شد.', true);
        $approval->archive($closed->refresh(), $manager);

        $this->writeCredentialsFile($users);

        $count = ServiceRequest::query()->where('submission_key', 'like', 'demo-seed-%')->count();
        $this->command?->info("Demo seed OK: {$count} درخواست، کاربران @demo.local — رمز: ".self::DEMO_PASSWORD);
        $this->command?->line('لیست ورود: storage/app/demo-credentials.txt');
    }

    /**
     * @return array<string, OrganizationalUnit>
     */
    private function seedUnits(): array
    {
        $defs = [
            ['code' => 'DEMO-SALES', 'name' => 'واحد فروش و ثبت درخواست'],
            ['code' => 'DEMO-QC', 'name' => 'واحد کنترل کیفیت'],
            ['code' => 'DEMO-WH', 'name' => 'واحد انبار و لجستیک'],
            ['code' => 'DEMO-SUPPORT', 'name' => 'واحد پشتیبانی مشتریان'],
        ];

        $units = [];
        foreach ($defs as $def) {
            $units[$def['code']] = OrganizationalUnit::query()->updateOrCreate(
                ['code' => $def['code']],
                ['name' => $def['name'], 'is_active' => true],
            );
        }

        return $units;
    }

    /**
     * @param  array<string, OrganizationalUnit>  $units
     * @return array<string, User>
     */
    private function seedStaff(array $units): array
    {
        $users = [];

        foreach ($this->staffBlueprint as $row) {
            $unitId = $row['unit_code'] ? $units[$row['unit_code']]->id : null;
            $user = User::query()->updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'mobile' => '0912'.str_pad((string) (1000000 + count($users)), 7, '0', STR_PAD_LEFT),
                    'password' => self::DEMO_PASSWORD,
                    'organizational_unit_id' => $unitId,
                    'is_active' => true,
                ],
            );
            if (! $user->hasRole($row['role'])) {
                $user->syncRoles([$row['role']]);
            }
            $users[$row['email']] = $user;
        }

        return $users;
    }

    private function purgePreviousDemo(): void
    {
        ServiceRequest::query()->where('submission_key', 'like', 'demo-seed-%')->each(function (ServiceRequest $request): void {
            $request->messages()->delete();
            $request->assignments()->delete();
            $request->statusHistories()->delete();
            $request->fieldValues()->delete();
            $request->attachments()->delete();
            $request->approvalDecisions()->delete();
            $request->views()->delete();
            $request->delete();
        });

        User::query()->where('email', 'like', '%@demo.local')->each(function (User $user): void {
            $user->syncRoles([]);
            $user->delete();
        });

        OrganizationalUnit::query()->where('code', 'like', 'DEMO-%')->delete();
    }

    /**
     * @return list<UploadedFile>
     */
    private function demoFiles(): array
    {
        return [UploadedFile::fake()->image('demo-doc.jpg')];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $fullName, int $typeId, string $purchaseDate, string $description): array
    {
        return [
            'full_name' => $fullName,
            'request_type_id' => $typeId,
            'purchase_date' => $purchaseDate,
            'invoice_number' => 'INV-'.random_int(1000, 9999),
            'product_name' => 'محصول نمونه',
            'description' => $description,
            'fields' => [],
        ];
    }

    /**
     * @param  array<string, User>  $users
     */
    private function writeCredentialsFile(array $users): void
    {
        $lines = [
            'ورود پنل admin: /admin/login (تب ایمیل)',
            'رمز پیش‌فرض demo (به‌جز admin): '.self::DEMO_PASSWORD,
            '',
            'login (نام کاربری) | email | password | نقش',
            '-------------------|-------|----------|-----',
            'admin | admin@admin.com | admin | super_admin',
        ];

        foreach ($this->staffBlueprint as $row) {
            $lines[] = sprintf(
                '%s | %s | %s | %s',
                $row['login'],
                $row['email'],
                self::DEMO_PASSWORD,
                $row['role'],
            );
        }

        $lines[] = '';
        $lines[] = 'مشتریان نمونه (OTP عمومی): موبایل 09121000001 تا 09121000010';
        $lines[] = 'تاریخ seed: '.JalaliDate::format(now());

        File::ensureDirectoryExists(storage_path('app'));
        File::put(storage_path('app/demo-credentials.txt'), implode(PHP_EOL, $lines).PHP_EOL);
    }
}
