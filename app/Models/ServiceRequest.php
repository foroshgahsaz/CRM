<?php

namespace App\Models;

use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Exceptions\WorkflowException;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'tracking_code',
    'customer_id',
    'request_type_id',
    'purchase_date',
    'invoice_number',
    'product_name',
    'purchased_quantity',
    'affected_quantity',
    'description',
    'status',
    'priority',
    'current_assignment_id',
    'current_owner_id',
    'current_unit_id',
    'submitted_at',
    'approved_by',
    'approved_at',
    'closed_at',
    'archived_at',
    'due_at',
    'submission_key',
    'customer_visible_summary',
])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    public static bool $allowWorkflowMutation = false;

    public static int $workflowDepth = 0;

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function withinWorkflow(callable $callback): mixed
    {
        self::$workflowDepth++;
        self::$allowWorkflowMutation = true;

        try {
            return $callback();
        } finally {
            self::$workflowDepth--;
            self::$allowWorkflowMutation = self::$workflowDepth > 0;
        }
    }

    protected static function booted(): void
    {
        static::updating(function (ServiceRequest $request): void {
            if (self::$allowWorkflowMutation) {
                return;
            }

            $guarded = ['status', 'current_assignment_id', 'current_owner_id', 'current_unit_id', 'approved_by', 'approved_at', 'closed_at', 'archived_at'];

            if ($request->isDirty($guarded)) {
                throw new WorkflowException('وضعیت و مسئول پرونده فقط از مسیر گردش کار قابل تغییر است.');
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function requestType(): BelongsTo
    {
        return $this->belongsTo(RequestType::class);
    }

    public function currentAssignment(): BelongsTo
    {
        return $this->belongsTo(RequestAssignment::class, 'current_assignment_id');
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_owner_id');
    }

    public function currentUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'current_unit_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(RequestAssignment::class)->orderBy('id');
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(RequestAssignment::class)->where('status', 'active');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(RequestMessage::class)->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class)->orderBy('id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class)->orderBy('id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(RequestView::class)->orderByDesc('last_viewed_at');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(RequestFieldValue::class)->orderBy('id');
    }

    public function approvalDecisions(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class)->orderBy('id');
    }

    /**
     * @param  Builder<ServiceRequest>  $query
     * @return Builder<ServiceRequest>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('requests.view_all')) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user): void {
            $query->where('current_owner_id', $user->id)
                ->orWhereHas('assignments', fn (Builder $assignments) => $assignments->where('to_user_id', $user->id));

            if ($user->organizational_unit_id && $user->can('requests.assign')) {
                $query->orWhere(function (Builder $inner) use ($user): void {
                    $inner->where('current_unit_id', $user->organizational_unit_id)
                        ->where('status', RequestStatus::AwaitingUnitClaim->value);
                });
            }
        });
    }

    /**
     * @param  Builder<ServiceRequest>  $query
     * @return Builder<ServiceRequest>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            RequestStatus::Closed->value,
            RequestStatus::Archived->value,
        ]);
    }

    /**
     * @param  Builder<ServiceRequest>  $query
     * @return Builder<ServiceRequest>
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->open()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNotIn('status', [RequestStatus::Approved->value]);
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! in_array($this->status, [RequestStatus::Approved, RequestStatus::Closed, RequestStatus::Archived], true);
    }

    public function isReadOnly(): bool
    {
        return $this->status === RequestStatus::Archived;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RequestStatus::class,
            'priority' => RequestPriority::class,
            'purchase_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'archived_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }
}
