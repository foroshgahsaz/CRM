<?php

namespace App\Support;

use App\Models\MessageTemplate;

class SmsIrPattern
{
    /**
     * @param  array<string, scalar|null>  $variables
     * @return list<array{name: string, value: string}>
     */
    public static function parameters(MessageTemplate $template, array $variables): array
    {
        $map = self::parameterMap($template);
        $parameters = [];

        foreach ($map as $variable => $providerName) {
            if (! array_key_exists($variable, $variables)) {
                throw new \InvalidArgumentException('متغیر '.$variable.' برای الگوی پیامک موجود نیست.');
            }

            $parameters[] = [
                'name' => $providerName,
                'value' => (string) $variables[$variable],
            ];
        }

        return $parameters;
    }

    public static function templateId(MessageTemplate $template): ?int
    {
        $id = $template->sms_ir_template_id;

        if ($id !== null && $id > 0) {
            return $id;
        }

        if ($template->event_key === 'otp') {
            $fromEnv = (int) config('sms.smsir.otp_template_id');

            return $fromEnv > 0 ? $fromEnv : null;
        }

        return null;
    }

    /**
     * @return array<string, string> variable => sms.ir parameter name
     */
    public static function parameterMap(MessageTemplate $template): array
    {
        $stored = $template->sms_ir_parameter_map;

        if (is_array($stored) && $stored !== []) {
            $clean = [];

            foreach ($stored as $variable => $name) {
                if (is_string($variable) && is_string($name) && $name !== '') {
                    $clean[$variable] = $name;
                }
            }

            if ($clean !== []) {
                return $clean;
            }
        }

        return self::defaultMap($template->event_key, $template->allowed_variables ?? []);
    }

    /**
     * @param  list<string>  $allowedVariables
     * @return array<string, string>
     */
    public static function defaultMap(string $eventKey, array $allowedVariables): array
    {
        $defaults = match ($eventKey) {
            'otp' => ['otp_code' => 'CODE'],
            'request_submitted' => ['company_name' => 'COMPANY', 'tracking_code' => 'CODE'],
            'customer_info_requested' => ['company_name' => 'COMPANY', 'tracking_code' => 'CODE'],
            'request_closed' => ['company_name' => 'COMPANY', 'tracking_code' => 'CODE', 'public_status' => 'STATUS'],
            default => [],
        };

        $map = [];

        foreach ($allowedVariables as $variable) {
            $map[$variable] = $defaults[$variable] ?? strtoupper($variable);
        }

        return $map;
    }
}
