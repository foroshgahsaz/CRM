<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Support\MobileNumber;

class CustomerSubmissionGuard
{
    public function openRequestForMobile(string $mobile): ?ServiceRequest
    {
        $mobile = MobileNumber::normalize($mobile);

        if ($mobile === null) {
            return null;
        }

        return ServiceRequest::query()
            ->open()
            ->whereHas('customer', fn ($query) => $query->where('mobile', $mobile))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();
    }
}
