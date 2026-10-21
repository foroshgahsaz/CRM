<?php

namespace App\Services;

use App\Enums\SmsStatus;
use App\Exceptions\WorkflowException;
use App\Models\NotificationDelivery;
use App\Models\SmsLog;
use App\Sms\OtpSmsSenderFactory;
use Illuminate\Database\QueryException;
use RuntimeException;

class OtpSmsDelivery
{
    public function __construct(
        private OtpSmsSenderFactory $senders,
    ) {}

    public function send(string $mobile, string $code, string $idempotencyKey): void
    {
        $existing = SmsLog::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing && in_array($existing->status, [SmsStatus::Sent, SmsStatus::Skipped], true)) {
            if ($existing->status !== SmsStatus::Sent) {
                throw new WorkflowException('ارسال پیامک با خطا مواجه شد. وضعیت ارسال قابل بررسی است.');
            }

            return;
        }

        $provider = config('sms.gateway') === 'sms_ir' ? 'sms_ir' : 'log';

        try {
            $this->senders->make()->sendOtp($mobile, $code);
        } catch (RuntimeException $exception) {
            $this->writeLog(
                $mobile,
                $idempotencyKey,
                $provider,
                SmsStatus::Failed,
                null,
                'provider',
                $exception->getMessage(),
                ($existing?->attempt ?? 0) + 1,
            );

            throw new WorkflowException($this->userFacingMessage($exception));
        }

        $this->writeLog(
            $mobile,
            $idempotencyKey,
            $provider,
            SmsStatus::Sent,
            null,
            null,
            null,
            ($existing?->attempt ?? 0) + 1,
            now(),
        );

        NotificationDelivery::query()->updateOrCreate(
            ['idempotency_key' => 'sms:'.$idempotencyKey],
            [
                'notification_type' => 'otp',
                'channel' => 'sms',
                'service_request_id' => null,
                'status' => SmsStatus::Sent->value,
                'delivered_at' => now(),
                'failure_reason' => null,
                'provider_reference' => null,
            ],
        );
    }

    private function userFacingMessage(RuntimeException $exception): string
    {
        $message = $exception->getMessage();

        if (str_starts_with($message, 'sms.ir:')) {
            return trim(substr($message, strlen('sms.ir:')));
        }

        return $message !== '' ? $message : 'ارسال پیامک با خطا مواجه شد.';
    }

    private function writeLog(
        string $mobile,
        string $idempotencyKey,
        string $provider,
        SmsStatus $status,
        ?string $reference,
        ?string $errorCode,
        ?string $errorMessage,
        int $attempt,
        mixed $sentAt = null,
    ): void {
        try {
            SmsLog::query()->updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'recipient' => $mobile,
                    'event_key' => 'otp',
                    'service_request_id' => null,
                    'provider' => $provider,
                    'provider_reference' => $reference,
                    'status' => $status,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
                    'attempt' => $attempt,
                    'sent_at' => $sentAt,
                ],
            );
        } catch (QueryException) {
            // idempotent race
        }
    }
}
