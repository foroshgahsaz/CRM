<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditLogService;
use App\Support\MobileNumber;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdminCommand extends Command
{
    protected $signature = 'crm:create-admin
        {--name= : نام مدیر}
        {--email= : ایمیل ورود}
        {--password= : رمز عبور}
        {--mobile= : موبایل اختیاری}';

    protected $description = 'ایجاد کاربر مدیر ارشد اولیه';

    public function handle(AuditLogService $audit): int
    {
        $this->call('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--force' => true]);

        $name = (string) ($this->option('name') ?: $this->ask('نام'));
        $email = (string) ($this->option('email') ?: $this->ask('ایمیل'));
        $password = (string) ($this->option('password') ?: Str::password(16));
        $generated = $this->option('password') === null;
        $mobile = MobileNumber::normalize($this->option('mobile'));

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'password' => $password,
            'is_active' => true,
        ]);
        $user->assignRole('super_admin');
        $audit->record('user.created', $user, 'success', ['role' => 'super_admin'], $user);

        $this->info('مدیر ارشد ایجاد شد.');

        if ($generated) {
            $this->warn('رمز عبور فقط همین یک‌بار نمایش داده می‌شود: '.$password);
        }

        return self::SUCCESS;
    }
}
