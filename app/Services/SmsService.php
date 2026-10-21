<?php

namespace App\Services;

use App\Enums\SmsStatus;
use App\Exceptions\RetryableSmsException;
use App\Exceptions\WorkflowException;
use App\Jobs\SendSmsJob;
use App\Models\MessageTemplate;
use App\Models\NotificationDelivery;
use App\Models\SmsLog;
use App\Sms\SmsGateway;
use App\Sms\SmsIrGateway;
use App\Sms\SmsResult;
use Illuminate\Database\QueryException;

class SmsService
{
    public function __construct(
        private SmsGateway $gateway,
        private SettingsService $settings,
    ) {}

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function queue(string $mobile, string $eventKey, array $variables, ?int $serviceRequestId, string $idempotencyKey): void
    {
        if ($eventKey !== 'otp' && ! $this->settings->getBool('sms_enabled', false)) {
            $this->writeLog($mobile, $eventKey, $serviceRequestId, SmsStatus::Skipped, $idempotencyKey, null, null, 'پیامک غیرفعال است.');

            return;
        }

        SendSmsJob::dispatch($mobile, $eventKey, $variables, $serviceRequestId, $idempotencyKey)->afterCommit();
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function sendImmediately(string $mobile, string $eventKey, array $variables, ?int $serviceRequestId, string $idempotencyKey): void
    {
        $result = $this->deliver($mobile, $eventKey, $variables, $serviceRequestId, $idempotencyKey);

        if (! $result) {
            throw new WorkflowException('ارسال پیامک با خطا مواجه شد. وضعیت ارسال قابل بررسی است.');
        }
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    public function deliver(string $mobile, string $eventKey, array $variables, ?int $serviceRequestId, string $idempotencyKey): bool
    {
        $existing = SmsLog::query()->where('idempotency_key', $idempotencyKey)->first();

        if ($existing && in_array($existing->status, [SmsStatus::Sent, SmsStatus::Skipped], true)) {
            return $existing->status === SmsStatus::Sent;
        }

        if ($eventKey !== 'otp' && ! $this->settings->getBool('sms_enabled', false)) {
            $this->writeLog($mobile, $eventKey, $serviceRequestId, SmsStatus::Skipped, $idempotencyKey, null, null, 'پیامک غیرفعال است.');

            return false;
        }

        $result = $this->dispatchToGateway($mobile, $eventKey, $variables);
        $status = $result->successful ? SmsStatus::Sent : SmsStatus::Failed;

        $log = $this->writeLog(
            $mobile,
            $eventKey,
            $serviceRequestId,
            $status,
            $idempotencyKey,
            $result->providerReference,
            $result->errorCode,
            $result->errorMessage,
            $result->successful ? now() : null,
            ($existing?->attempt ?? 0) + 1,
        );

        NotificationDelivery::query()->updateOrCreate(
            ['idempotency_key' => 'sms:'.$idempotencyKey],
            [
                'notification_type' => $eventKey,
                'channel' => 'sms',
                'service_request_id' => $serviceRequestId,
                'status' => $status->value,
                'delivered_at' => $result->successful ? now() : null,
                'failure_reason' => $result->errorMessage,
                'provider_reference' => $result->providerReference,
            ],
        );

        if (! $result->successful && $result->retryable) {
            throw new RetryableSmsException('ارسال پیامک موقتاً ناموفق بود.');
        }

        return $log->status === SmsStatus::Sent;
    }

    /**
     * @param  array<string, scalar|null>  $variables
     */
    /**
     * @param  array<string, scalar|null>  $variables
     */
    private function dispatchToGateway(string $mobile, string $eventKey, array $variables): SmsResult
    {
        if ($eventKey === 'otp' && $this->gateway instanceof SmsIrGateway) {
            $templateId = $this->gateway->otpTemplateId();

            if ($templateId !== null) {
                if (! array_key_exists('otp_code', $variables)) {
                    throw new WorkflowException('اطلاعات لازم برای قالب پیامک کامل نیست.');
                }

                $parameterName = (string) config('sms.smsir.otp_parameter_name', 'CODE');

                return $this->gateway->sendVerify($mobile, $templateId, [
                    ['name' => $parameterName, 'value' => (string) $variables['otp_code']],
                ]);
            }
        }

        $message = $this->render($eventKey, $variables);

        return $this->gateway->send($mobile, $message);
    }

    public function render(string $eventKey, array $variables): string
    {
        $template = MessageTemplate::query()->where('event_key', $eventKey)->where('is_active', true)->first();

        if ($template === null) {
            throw new WorkflowException('قالب پیامک برای این رویداد فعال نیست.');
        }

        $body = $template->body;

        foreach ($template->allowed_variables as $variable) {
            if (! array_key_exists($variable, $variables)) {
                throw new WorkflowException('اطلاعات لازم برای قالب پیامک کامل نیست.');
            }

            if ($variable === 'otp_code' && $eventKey !== 'otp') {
                throw new WorkflowException('استفاده از رمز یک‌بارمصرف در این قالب مجاز نیست.');
            }

            $body = str_replace('{'.$variable.'}', (string) $variables[$variable], $body);
        }

        return $body;
    }

    private function writeLog(
        string $mobile,
        string $eventKey,
        ?int $serviceRequestId,
        SmsStatus $status,
        string $idempotencyKey,
        ?string $reference,
        ?string $errorCode,
        ?string $errorMessage,
        mixed $sentAt = null,
        int $attempt = 1,
    ): SmsLog {
        try {
            return SmsLog::query()->updateOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'recipient' => $mobile,
                    'event_key' => $eventKey,
                    'service_request_id' => $serviceRequestId,
                    'provider' => $this->gateway->name(),
                    'provider_reference' => $reference,
                    'status' => $status,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
                    'attempt' => $attempt,
                    'sent_at' => $sentAt,
                ],
            );
        } catch (QueryException) {
            return SmsLog::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
        }
    }
}
