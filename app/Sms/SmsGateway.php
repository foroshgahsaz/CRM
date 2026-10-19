<?php

namespace App\Sms;

interface SmsGateway
{
    public function send(string $mobile, string $message): SmsResult;

    public function name(): string;
}
