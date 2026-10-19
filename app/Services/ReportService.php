<?php

namespace App\Services;

use App\Enums\RequestStatus;
use App\Models\ApprovalDecision;
use App\Models\RequestAssignment;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Definitions:
     * - submitted scope: service_requests.submitted_at is inside the selected range.
     * - handling time: submitted_at until closed_at.
     * - time to first view: submitted_at until the first assignment first_viewed_at.
     * - time to start: submitted_at until the first assignment work_started_at.
     * - expert time: assignment work_started_at until completed_at, for assignments completed in the range.
     * - time to approval: submitted_at until approved_at.
     * - workload: assignments whose created_at is inside the range.
     *
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to, ?int $typeId = null, ?int $unitId = null): array
    {
        $requests = ServiceRequest::query()
            ->whereBetween('submitted_at', [$from, $to])
            ->when($typeId, fn (Builder $query) => $query->where('request_type_id', $typeId))
            ->when($unitId, fn (Builder $query) => $query->where('current_unit_id', $unitId));

        $ids = (clone $requests)->pluck('id');

        return [
            'submitted_count' => (clone $requests)->count(),
            'by_status' => (clone $requests)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'by_type' => (clone $requests)
                ->join('request_types', 'request_types.id', '=', 'service_requests.request_type_id')
                ->selectRaw('request_types.name as name, count(*) as total')
                ->groupBy('request_types.name')
                ->pluck('total', 'name'),
            'unassigned_count' => (clone $requests)->where('status', RequestStatus::PendingAssignment)->count(),
            'overdue_count' => (clone $requests)->overdue()->count(),
            'closed_count' => ServiceRequest::query()->whereBetween('closed_at', [$from, $to])->when($typeId, fn (Builder $query) => $query->where('request_type_id', $typeId))->count(),
            'rejection_count' => ApprovalDecision::query()->whereIn('service_request_id', $ids)->where('decision', 'rejected')->count(),
            'avg_assignments' => round((float) RequestAssignment::query()->whereIn('service_request_id', $ids)->count() / max($ids->count(), 1), 2),
            'avg_hours_to_first_view' => $this->averageHours($ids, 'first_viewed_at'),
            'avg_hours_to_start' => $this->averageHours($ids, 'work_started_at'),
            'avg_expert_hours' => $this->averageExpertHours($from, $to),
            'avg_hours_to_approval' => $this->averageColumnHours($from, $to, 'approved_at', $typeId),
            'avg_handling_hours' => $this->averageColumnHours($from, $to, 'closed_at', $typeId),
            'open_by_owner' => (clone $requests)->open()->whereNotNull('current_owner_id')
                ->join('users', 'users.id', '=', 'service_requests.current_owner_id')
                ->selectRaw('users.name as name, count(*) as total')
                ->groupBy('users.name')
                ->pluck('total', 'name'),
            'open_by_unit' => (clone $requests)->open()->whereNotNull('current_unit_id')
                ->join('organizational_units', 'organizational_units.id', '=', 'service_requests.current_unit_id')
                ->selectRaw('organizational_units.name as name, count(*) as total')
                ->groupBy('organizational_units.name')
                ->pluck('total', 'name'),
            'workload' => RequestAssignment::query()
                ->whereBetween('request_assignments.created_at', [$from, $to])
                ->whereNotNull('to_user_id')
                ->join('users', 'users.id', '=', 'request_assignments.to_user_id')
                ->selectRaw('users.name as name, count(*) as total')
                ->groupBy('users.name')
                ->pluck('total', 'name'),
        ];
    }

    /**
     * @param  Collection<int, int>  $ids
     */
    private function averageHours($ids, string $column): ?float
    {
        if ($ids->isEmpty()) {
            return null;
        }

        $value = RequestAssignment::query()
            ->selectRaw('avg('.$this->hourDiff('service_requests.submitted_at', 'request_assignments.'.$column).') as hours')
            ->join('service_requests', 'service_requests.id', '=', 'request_assignments.service_request_id')
            ->whereIn('service_requests.id', $ids)
            ->whereNotNull('request_assignments.'.$column)
            ->whereRaw('request_assignments.id = (select min(id) from request_assignments ra where ra.service_request_id = service_requests.id)')
            ->value('hours');

        return $value === null ? null : round((float) $value, 2);
    }

    private function averageExpertHours(Carbon $from, Carbon $to): ?float
    {
        $value = RequestAssignment::query()
            ->whereBetween('completed_at', [$from, $to])
            ->whereNotNull('work_started_at')
            ->selectRaw('avg('.$this->hourDiff('work_started_at', 'completed_at').') as hours')
            ->value('hours');

        return $value === null ? null : round((float) $value, 2);
    }

    private function averageColumnHours(Carbon $from, Carbon $to, string $column, ?int $typeId): ?float
    {
        $value = ServiceRequest::query()
            ->whereBetween($column, [$from, $to])
            ->when($typeId, fn (Builder $query) => $query->where('request_type_id', $typeId))
            ->selectRaw('avg('.$this->hourDiff('submitted_at', $column).') as hours')
            ->value('hours');

        return $value === null ? null : round((float) $value, 2);
    }

    private function hourDiff(string $start, string $end): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return '(julianday('.$end.') - julianday('.$start.')) * 24';
        }

        return 'timestampdiff(MINUTE, '.$start.', '.$end.') / 60';
    }
}
