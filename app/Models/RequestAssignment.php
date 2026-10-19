<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\AssignmentType;
use Database\Factories\RequestAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'service_request_id',
    'parent_assignment_id',
    'from_user_id',
    'from_unit_id',
    'to_user_id',
    'to_unit_id',
    'assignment_type',
    'status',
    'active_lock',
    'instructions',
    'reason',
    'first_viewed_at',
    'accepted_at',
    'work_started_at',
    'due_at',
    'completed_at',
    'completion_summary',
])]
class RequestAssignment extends Model
{
    /** @use HasFactory<RequestAssignmentFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_assignment_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'from_unit_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'to_unit_id');
    }

    public function isActive(): bool
    {
        return $this->status === AssignmentStatus::Active;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'status' => AssignmentStatus::class,
            'first_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'work_started_at' => 'datetime',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
