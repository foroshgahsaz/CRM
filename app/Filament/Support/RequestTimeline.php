<?php

namespace App\Filament\Support;

use App\Enums\ApprovalDecisionType;
use App\Enums\AssignmentStatus;
use App\Enums\MessageType;
use App\Enums\MessageVisibility;
use App\Enums\RequestPriority;
use App\Enums\RequestStatus;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Support\JalaliDate;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the view models for the staff request page: header, progress
 * stepper and one merged, chronological timeline of everything that happened.
 */
class RequestTimeline
{
    /**
     * @var list<string>
     */
    public const RELATIONS = [
        'customer',
        'requestType',
        'currentOwner',
        'currentUnit',
        'statusHistories.changer',
        'assignments.fromUser',
        'assignments.toUser',
        'assignments.fromUnit',
        'assignments.toUnit',
        'messages.sender',
        'messages.customer',
        'attachments.uploadedBy',
        'approvalDecisions.decider',
        'inventoryAllocations.allocator',
        'inventoryAllocations.canceller',
        'views.user',
    ];

    public static function load(ServiceRequest $request): ServiceRequest
    {
        return $request->loadMissing(self::RELATIONS);
    }

    /**
     * @return Collection<int, RequestMessage>
     */
    public static function visibleMessages(ServiceRequest $request, ?User $viewer): Collection
    {
        $canSeeAdmin = $viewer?->can('viewAdminNotes', $request) ?? false;

        return self::load($request)->messages
            ->filter(fn ($message): bool => $message->visibility !== MessageVisibility::AdminOnly || $canSeeAdmin)
            ->sortByDesc('id')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function header(ServiceRequest $request): array
    {
        self::load($request);

        $due = $request->due_at;
        $overdue = $request->isOverdue();
        $finished = in_array($request->status, [RequestStatus::Approved, RequestStatus::Closed, RequestStatus::Archived], true);

        return [
            'tracking' => $request->tracking_code,
            'status' => $request->status->label(),
            'statusColor' => self::statusColor($request->status),
            'priority' => $request->priority->label(),
            'priorityColor' => match ($request->priority) {
                RequestPriority::Urgent => 'danger',
                RequestPriority::High => 'warning',
                RequestPriority::Low => 'gray',
                default => 'info',
            },
            'type' => $request->requestType?->name,
            'customer' => $request->customer?->full_name,
            'product' => $request->product_name,
            'owner' => $request->currentOwner?->name,
            'unit' => $request->currentUnit?->name,
            'submitted' => JalaliDate::format($request->submitted_at),
            'submittedAgo' => $request->submitted_at?->locale('fa')->diffForHumans(),
            'due' => $due ? JalaliDate::format($due) : null,
            'dueHint' => match (true) {
                $due === null => null,
                $finished => 'رسیدگی به پایان رسیده',
                $overdue => 'معوق: '.$due->locale('fa')->diffForHumans(),
                default => 'مهلت: '.$due->locale('fa')->diffForHumans(),
            },
            'overdue' => $overdue,
            'steps' => self::steps($request->status),
            'counts' => [
                'assignments' => $request->assignments->count(),
                'attachments' => $request->attachments->count(),
                'allocations' => $request->inventoryAllocations->whereNull('cancelled_at')->count(),
            ],
        ];
    }

    /**
     * @return list<array{label: string, state: string}>
     */
    public static function steps(RequestStatus $status): array
    {
        $labels = ['ثبت درخواست', 'تخصیص', 'رسیدگی کارشناس', 'تأیید نهایی', 'بسته شدن'];

        $current = match ($status) {
            RequestStatus::PendingAssignment, RequestStatus::AwaitingUnitClaim => 1,
            RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::WaitingCustomer, RequestStatus::Reopened => 2,
            RequestStatus::PendingFinalApproval => 3,
            RequestStatus::Approved => 4,
            RequestStatus::Closed, RequestStatus::Archived => 5,
        };

        return array_map(fn (int $index, string $label): array => [
            'label' => $label,
            'state' => $index < $current ? 'done' : ($index === $current ? 'current' : 'upcoming'),
        ], array_keys($labels), $labels);
    }

    /**
     * Newest first, grouped by Jalali day.
     *
     * @return list<array{day: string, events: list<array<string, mixed>>}>
     */
    public static function grouped(ServiceRequest $request, ?User $viewer): array
    {
        return self::events($request, $viewer)
            ->groupBy(fn (array $event): string => JalaliDate::format($event['at'], 'l j F Y'))
            ->map(fn (Collection $events, string $day): array => ['day' => $day, 'events' => $events->values()->all()])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function events(ServiceRequest $request, ?User $viewer): Collection
    {
        self::load($request);
        $events = collect();

        if ($request->submitted_at) {
            $events->push(self::event($request->submitted_at, 'submitted', 'heroicon-o-inbox-arrow-down', 'primary',
                'درخواست ثبت شد',
                $request->customer?->full_name,
                $request->description,
                array_filter([$request->requestType?->name, $request->product_name]),
            ));
        }

        foreach ($request->statusHistories as $history) {
            if ($history->from_status === null) {
                continue;
            }

            $events->push(self::event($history->created_at, 'status', 'heroicon-o-arrow-path', self::statusColor($history->to_status),
                'وضعیت: '.$history->to_status->label(),
                $history->changer?->name ?? 'سامانه',
                $history->reason,
                [$history->from_status->label().' ← '.$history->to_status->label()],
            ));
        }

        foreach ($request->assignments as $assignment) {
            $target = $assignment->toUser?->name ?? ($assignment->toUnit ? 'واحد '.$assignment->toUnit->name : '—');

            $events->push(self::event($assignment->created_at, 'assignment', 'heroicon-o-arrows-right-left', 'info',
                $assignment->assignment_type->label().' به '.$target,
                $assignment->fromUser?->name ?? 'سامانه',
                trim(collect([
                    $assignment->reason ? 'دلیل: '.$assignment->reason : null,
                    $assignment->instructions ? 'دستور: '.$assignment->instructions : null,
                ])->filter()->implode("\n")) ?: null,
                [$assignment->status->label()],
            ));

            if ($assignment->first_viewed_at) {
                $events->push(self::event($assignment->first_viewed_at, 'viewed', 'heroicon-o-eye', 'gray',
                    'مشاهدهٔ ارجاع توسط '.$target,
                    null,
                    null,
                    [],
                ));
            }

            if ($assignment->completed_at && $assignment->status === AssignmentStatus::Completed) {
                $events->push(self::event($assignment->completed_at, 'completion', 'heroicon-o-check-badge', 'success',
                    'کارشناسی تکمیل شد',
                    $assignment->toUser?->name,
                    $assignment->completion_summary,
                    [],
                ));
            }
        }

        foreach (self::visibleMessages($request, $viewer) as $message) {
            $events->push(self::event($message->created_at, 'message', match ($message->message_type) {
                MessageType::AdminNote => 'heroicon-o-lock-closed',
                MessageType::InfoRequest => 'heroicon-o-question-mark-circle',
                MessageType::CustomerVisible => 'heroicon-o-user',
                default => 'heroicon-o-chat-bubble-left-ellipsis',
            }, match ($message->visibility) {
                MessageVisibility::AdminOnly => 'warning',
                MessageVisibility::CustomerVisible => 'primary',
                default => 'gray',
            },
                $message->message_type->label(),
                $message->sender?->name ?? $message->customer?->full_name ?? 'مشتری',
                $message->body,
                [$message->visibility->label()],
            ));
        }

        foreach ($request->attachments as $attachment) {
            $events->push(self::event($attachment->created_at, 'attachment', 'heroicon-o-paper-clip', 'gray',
                'مدرک بارگذاری شد: '.$attachment->original_name,
                $attachment->uploadedBy?->name ?? $request->customer?->full_name ?? 'مشتری',
                null,
                [$attachment->category->label()],
                route('staff.attachments.show', $attachment),
            ));
        }

        foreach ($request->approvalDecisions as $decision) {
            $approved = $decision->decision === ApprovalDecisionType::Approved;

            $events->push(self::event($decision->created_at, 'approval', $approved ? 'heroicon-o-shield-check' : 'heroicon-o-arrow-uturn-left', $approved ? 'success' : 'danger',
                $decision->decision->label(),
                $decision->decider?->name,
                $decision->reason,
                [],
            ));
        }

        foreach ($request->inventoryAllocations as $allocation) {
            $events->push(self::event($allocation->created_at, 'inventory', 'heroicon-o-archive-box-arrow-down', 'success',
                'ارجاع کالا از انبار: '.$allocation->product_name_snapshot,
                $allocation->allocator?->name,
                $allocation->note,
                [$allocation->product_code_snapshot, $allocation->warehouse_name_snapshot],
            ));

            if ($allocation->cancelled_at) {
                $events->push(self::event($allocation->cancelled_at, 'inventory', 'heroicon-o-x-circle', 'danger',
                    'لغو ارجاع کالا: '.$allocation->product_name_snapshot,
                    $allocation->canceller?->name,
                    $allocation->cancel_reason,
                    [],
                ));
            }
        }

        return $events
            ->filter(fn (array $event): bool => $event['at'] instanceof CarbonInterface)
            ->sortByDesc(fn (array $event): string => $event['at']->format('YmdHisu').str_pad((string) $event['order'], 3, '0', STR_PAD_LEFT))
            ->values();
    }

    public static function filterKey(string $kind): string
    {
        return match ($kind) {
            'viewed', 'completion' => 'assignment',
            'submitted' => 'status',
            default => $kind,
        };
    }

    public static function statusColor(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::PendingAssignment, RequestStatus::AwaitingUnitClaim => 'warning',
            RequestStatus::Assigned, RequestStatus::InProgress, RequestStatus::Reopened => 'info',
            RequestStatus::WaitingCustomer => 'warning',
            RequestStatus::PendingFinalApproval => 'primary',
            RequestStatus::Approved, RequestStatus::Closed => 'success',
            RequestStatus::Archived => 'gray',
        };
    }

    public static function tone(string $color, string $part = 'chip'): string
    {
        $tones = [
            'primary' => [
                'dot' => 'bg-indigo-500 text-white ring-indigo-100 dark:ring-indigo-500/20',
                'chip' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-500/10 dark:text-indigo-300',
            ],
            'info' => [
                'dot' => 'bg-sky-500 text-white ring-sky-100 dark:ring-sky-500/20',
                'chip' => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-300',
            ],
            'success' => [
                'dot' => 'bg-emerald-500 text-white ring-emerald-100 dark:ring-emerald-500/20',
                'chip' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-300',
            ],
            'warning' => [
                'dot' => 'bg-amber-500 text-white ring-amber-100 dark:ring-amber-500/20',
                'chip' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300',
            ],
            'danger' => [
                'dot' => 'bg-rose-500 text-white ring-rose-100 dark:ring-rose-500/20',
                'chip' => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-500/10 dark:text-rose-300',
            ],
            'gray' => [
                'dot' => 'bg-slate-400 text-white ring-slate-100 dark:ring-slate-500/20',
                'chip' => 'bg-slate-100 text-slate-700 ring-slate-500/20 dark:bg-white/5 dark:text-slate-300',
            ],
        ];

        return $tones[$color][$part] ?? $tones['gray'][$part];
    }

    public static function fileSize(?int $bytes): string
    {
        if (! $bytes) {
            return '—';
        }

        return $bytes >= 1048576
            ? number_format($bytes / 1048576, 1).' مگابایت'
            : max(1, (int) round($bytes / 1024)).' کیلوبایت';
    }

    /**
     * @param  list<string>  $chips
     * @return array<string, mixed>
     */
    private static function event(?CarbonInterface $at, string $kind, string $icon, string $color, string $title, ?string $actor, ?string $body, array $chips, ?string $url = null): array
    {
        static $order = 0;

        return [
            'at' => $at,
            'time' => $at ? JalaliDate::format($at, 'H:i') : '',
            'ago' => $at?->locale('fa')->diffForHumans(),
            'kind' => $kind,
            'icon' => $icon,
            'color' => $color,
            'title' => $title,
            'actor' => $actor,
            'body' => $body,
            'chips' => array_values(array_filter($chips)),
            'url' => $url,
            'order' => $order++,
        ];
    }
}
