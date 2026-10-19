<?php

namespace App\Models;

use App\Enums\ApprovalDecisionType;
use Database\Factories\ApprovalDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['service_request_id', 'decision', 'decided_by', 'reason', 'assignment_id'])]
class ApprovalDecision extends Model
{
    /** @use HasFactory<ApprovalDecisionFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => ApprovalDecisionType::class,
        ];
    }
}
