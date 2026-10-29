<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event' => ['nullable', 'string', 'max:50'],
            'log_name' => ['nullable', 'string', 'max:191'],
            'subject_type' => ['nullable', 'string', 'max:191'],
            'causer_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $activityLogs = $this->applyFilters(
            Activity::query()->with(['causer', 'subject'])->latest(),
            $filters,
        )->paginate(15)->withQueryString();

        $summaryQuery = $this->applyFilters(Activity::query(), $filters);
        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'today' => (clone $summaryQuery)->whereDate('created_at', today())->count(),
            'created' => (clone $summaryQuery)->where('event', 'created')->count(),
            'updated' => (clone $summaryQuery)->where('event', 'updated')->count(),
            'deleted' => (clone $summaryQuery)->where('event', 'deleted')->count(),
        ];

        $eventOptions = Activity::query()
            ->whereNotNull('event')->distinct()->orderBy('event')->pluck('event');
        $logNameOptions = Activity::query()
            ->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name');
        $subjectTypeOptions = Activity::query()
            ->whereNotNull('subject_type')->distinct()->orderBy('subject_type')->pluck('subject_type')
            ->map(fn (string $subjectType): array => [
                'value' => $subjectType,
                'label' => class_basename($subjectType),
            ]);
        $causerOptions = User::query()
            ->select('id', 'name', 'email')->orderBy('name')->get();

        return view('admin.activity-log.index', compact(
            'activityLogs',
            'summary',
            'filters',
            'eventOptions',
            'logNameOptions',
            'subjectTypeOptions',
            'causerOptions',
        ));
    }

    /** @param array<string, mixed> $filters */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('causer_type', 'like', "%{$search}%")
                    ->orWhere('properties', 'like', "%{$search}%");
            });
        }

        if (filled($filters['event'] ?? null)) {
            $query->where('event', $filters['event']);
        }
        if (filled($filters['log_name'] ?? null)) {
            $query->where('log_name', $filters['log_name']);
        }
        if (filled($filters['subject_type'] ?? null)) {
            $query->where('subject_type', $filters['subject_type']);
        }
        if (filled($filters['causer_id'] ?? null)) {
            $query->where('causer_type', User::class)
                ->where('causer_id', (int) $filters['causer_id']);
        }
        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
