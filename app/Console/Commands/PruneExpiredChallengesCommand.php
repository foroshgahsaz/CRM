<?php

namespace App\Console\Commands;

use App\Models\CustomerAccessSession;
use App\Models\OtpChallenge;
use Illuminate\Console\Command;

class PruneExpiredChallengesCommand extends Command
{
    protected $signature = 'crm:prune-expired-challenges';

    protected $description = 'حذف چالش‌های OTP و نشست‌های منقضی مشتری';

    public function handle(): int
    {
        $otp = OtpChallenge::query()->where('created_at', '<', now()->subDays(7))->delete();
        $sessions = CustomerAccessSession::query()->where('expires_at', '<', now()->subDay())->delete();

        $this->info("OTP حذف‌شده: {$otp} / نشست حذف‌شده: {$sessions}");

        return self::SUCCESS;
    }
}
