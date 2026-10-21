<?php

namespace App\Contracts;

interface SmsOtpSender
{
    public function sendOtp(string $phone, string $code): void;
}
