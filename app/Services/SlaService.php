<?php

namespace App\Services;

use App\Models\ServiceRequest;
use App\Models\SlaPolicy;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SlaService
{
    public function __construct(private SettingsService $settings) {}

    public function apply(ServiceRequest $request): void
    {
        if (! $this->settings->getBool('sla_enabled', true)) {
            $request->due_at = null;

            return;
        }

        $policy = $this->match($request);

        if ($policy === null) {
            return;
        }

        $request->due_at = $policy->use_business_hours
            ? $this->addBusinessMinutes(now(), $policy->resolution_minutes)
            : now()->addMinutes($policy->resolution_minutes);
    }

    public function match(ServiceRequest $request): ?SlaPolicy
    {
        $best = null;
        $bestScore = -1;

        foreach (SlaPolicy::query()->where('is_active', true)->orderBy('id')->get() as $policy) {
            if ($policy->request_type_id !== null && $policy->request_type_id !== $request->request_type_id) {
                continue;
            }

            if ($policy->priority !== null && $policy->priority !== $request->priority) {
                continue;
            }

            if ($policy->organizational_unit_id !== null && $policy->organizational_unit_id !== $request->current_unit_id) {
                continue;
            }

            $score = ($policy->request_type_id ? 4 : 0)
                + ($policy->priority ? 2 : 0)
                + ($policy->organizational_unit_id ? 1 : 0);

            if ($score > $bestScore) {
                $best = $policy;
                $bestScore = $score;
            }
        }

        return $best;
    }

    public function addBusinessMinutes(CarbonInterface $start, int $minutes): Carbon
    {
        $cursor = Carbon::parse($start);
        $remaining = $minutes;
        $guard = 0;

        while ($remaining > 0 && $guard < 20000) {
            $guard++;

            if (! $this->isBusinessDay($cursor)) {
                $cursor = $this->nextOpening($cursor);

                continue;
            }

            [$open, $close] = $this->window($cursor);

            if ($cursor->lt($open)) {
                $cursor = $open->copy();
            }

            if ($cursor->gte($close)) {
                $cursor = $this->nextOpening($cursor);

                continue;
            }

            $available = (int) floor(($close->getTimestamp() - $cursor->getTimestamp()) / 60);
            $consume = min($remaining, max($available, 0));
            $cursor = $cursor->copy()->addMinutes($consume);
            $remaining -= $consume;

            if ($remaining > 0) {
                $cursor = $this->nextOpening($cursor);
            }
        }

        return $cursor;
    }

    public function isBusinessDay(CarbonInterface $date): bool
    {
        $allowed = $this->settings->get('business_days', config('crm.business_days'));
        $map = ['sun' => 0, 'mon' => 1, 'tue' => 2, 'wed' => 3, 'thu' => 4, 'fri' => 5, 'sat' => 6];
        $days = collect(is_array($allowed) ? $allowed : [])
            ->map(fn (mixed $day): ?int => $map[$day] ?? null)
            ->filter(fn (?int $day): bool => $day !== null)
            ->all();

        if (! in_array($date->dayOfWeek, $days, true)) {
            return false;
        }

        $holidays = $this->settings->get('holidays', []);

        return ! in_array($date->toDateString(), is_array($holidays) ? $holidays : [], true);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(CarbonInterface $date): array
    {
        $start = (string) $this->settings->get('business_start', '08:00');
        $end = (string) $this->settings->get('business_end', '17:00');

        return [
            Carbon::parse($date)->setTimeFromTimeString($start),
            Carbon::parse($date)->setTimeFromTimeString($end),
        ];
    }

    private function nextOpening(CarbonInterface $date): Carbon
    {
        $cursor = Carbon::parse($date)->addDay()->startOfDay();

        for ($i = 0; $i < 21; $i++) {
            if ($this->isBusinessDay($cursor)) {
                return $this->window($cursor)[0];
            }

            $cursor->addDay();
        }

        return $cursor;
    }
}
