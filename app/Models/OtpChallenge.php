<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Database\Factories\OtpChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'mobile',
    'purpose',
    'tracking_code',
    'code_hash',
    'expires_at',
    'verified_at',
    'attempts',
    'max_attempts',
    'last_sent_at',
    'resend_available_at',
    'send_count',
    'provider_reference',
    'ip_address',
    'consumed_at',
])]
class OtpChallenge extends Model
{
    /** @use HasFactory<OtpChallengeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purpose' => OtpPurpose::class,
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }
}
