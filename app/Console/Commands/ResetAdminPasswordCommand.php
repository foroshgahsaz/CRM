<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetAdminPasswordCommand extends Command
{
    protected $signature = 'crm:reset-admin-password
        {email : ایمیل ورود admin}
        {--password= : رمز جدید؛ در غیر این صورت تصادفی چاپ می‌شود}';

    protected $description = 'تنظیم مجدد رمز کاربر (برای فراموشی رمز admin)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = (string) ($this->option('password') ?: Str::password(16));
        $generated = $this->option('password') === null;

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error('کاربری با این ایمیل یافت نشد.');

            return self::FAILURE;
        }

        if (strlen($password) < 10) {
            $this->error('رمز باید حداقل ۱۰ کاراکتر باشد.');

            return self::FAILURE;
        }

        $user->forceFill(['password' => $password])->save();

        $this->info('رمز برای '.$email.' به‌روز شد.');

        if ($generated) {
            $this->warn('رمز جدید (فقط همین‌بار): '.$password);
        }

        $this->line('ورود پنل: ایمیل + رمز، یا موبایل ثبت‌شده + OTP (اگر --mobile روی کاربر ست شده).');

        return self::SUCCESS;
    }
}
