@extends('admin.master')

@section('title', 'Activity Log')

@section('content')
    @php
        $eventBadgeClasses = [
            'created' => 'badge-success',
            'updated' => 'badge-warning',
            'deleted' => 'badge-danger',
        ];

        $formatValue = function ($value) {
            if (is_null($value) || $value === '') {
                return '-';
            }
            if (is_bool($value)) {
                return $value ? 'Yes' : 'No';
            }
            if (is_array($value) || is_object($value)) {
                $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                return $json !== false ? $json : '-';
            }

            return (string) $value;
        };

        $resolveModelLabel = function ($model, ?string $fallbackType, $fallbackId) {
            if (! $model) {
                $type = class_basename((string) $fallbackType);

                return $type !== '' ? $type . ($fallbackId ? ' #' . $fallbackId : '') : 'System';
            }
            foreach (['name', 'title', 'code', 'sku', 'email'] as $attribute) {
                if (! empty($model->{$attribute})) {
                    return $model->{$attribute};
                }
            }

            return class_basename($model::class) . ' #' . $model->getKey();
        };
    @endphp

    <div class="page-breadcrumb border-bottom">
        <div class="row mb-2">
            <div class="col-md-6 align-self-center">
                <h5 class="page-title text-uppercase mb-0"><i class="mdi mdi-history mr-2"></i>Activity Log</h5>
            </div>
            <div class="col-md-6 align-self-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-md-end mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Activity Log</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid activity-log-page">
        <div class="activity-summary-grid">
            @foreach ([
                ['total', 'Total Logs', 'Matching the current filters.', 'primary', 'format-list-bulleted'],
                ['today', 'Today', 'Entries recorded today.', 'info', 'calendar-today'],
                ['created', 'Created', 'New record events.', 'success', 'plus-circle-outline'],
                ['updated', 'Updated', 'Changed record events.', 'warning', 'pencil-outline'],
                ['deleted', 'Deleted', 'Removed record events.', 'danger', 'delete-outline'],
            ] as [$key, $label, $meta, $color, $icon])
                <div class="card activity-summary-card">
                    <div class="card-body">
                        <span class="summary-icon summary-icon-{{ $color }}"><i class="mdi mdi-{{ $icon }}"></i></span>
                        <span class="summary-label">{{ $label }}</span>
                        <h3 class="summary-value">{{ number_format($summary[$key]) }}</h3>
                        <p class="summary-meta mb-0">{{ $meta }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card activity-card">
            <div class="card-header bg-white activity-card-header">
                <div>
                    <h5 class="mb-1">Activity Logs</h5>
                    <p class="text-muted mb-0">Review user actions, model changes, and request context.</p>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('activity-log.view') }}" class="activity-filter-panel mb-4">
                    <div class="form-row">
                        <div class="form-group col-xl-3 col-md-6">
                            <label for="search">Search</label>
                            <input type="text" class="form-control" id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Description, event, or payload">
                        </div>
                        <div class="form-group col-xl-2 col-md-6">
                            <label for="event">Event</label>
                            <select name="event" id="event" class="form-control">
                                <option value="">All Events</option>
                                @foreach ($eventOptions as $eventOption)
                                    <option value="{{ $eventOption }}" @selected(($filters['event'] ?? '') === $eventOption)>{{ ucfirst($eventOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-xl-2 col-md-6">
                            <label for="log_name">Log Name</label>
                            <select name="log_name" id="log_name" class="form-control">
                                <option value="">All Logs</option>
                                @foreach ($logNameOptions as $logNameOption)
                                    <option value="{{ $logNameOption }}" @selected(($filters['log_name'] ?? '') === $logNameOption)>{{ ucfirst($logNameOption) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-xl-2 col-md-6">
                            <label for="subject_type">Subject Type</label>
                            <select name="subject_type" id="subject_type" class="form-control">
                                <option value="">All Subjects</option>
                                @foreach ($subjectTypeOptions as $subjectTypeOption)
                                    <option value="{{ $subjectTypeOption['value'] }}" @selected(($filters['subject_type'] ?? '') === $subjectTypeOption['value'])>{{ $subjectTypeOption['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-xl-3 col-md-6">
                            <label for="causer_id">Actor</label>
                            <select name="causer_id" id="causer_id" class="form-control">
                                <option value="">All Actors</option>
                                @foreach ($causerOptions as $causerOption)
                                    <option value="{{ $causerOption->id }}" @selected((string) ($filters['causer_id'] ?? '') === (string) $causerOption->id)>{{ $causerOption->name }} ({{ $causerOption->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-xl-2 col-md-4">
                            <label for="date_from">From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="form-group col-xl-2 col-md-4">
                            <label for="date_to">To</label>
                            <input type="date" class="form-control @error('date_to') is-invalid @enderror" id="date_to" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                            @error('date_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-xl-8 col-md-4 activity-filter-actions">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-search mr-1"></i>Apply Filters</button>
                            <a href="{{ route('activity-log.view') }}" class="btn btn-light ml-2">Reset</a>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table activity-log-table mb-0">
                        <thead>
                            <tr><th>#</th><th>When</th><th>Actor</th><th>Action</th><th>Subject</th><th>Description</th><th class="text-center">Details</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($activityLogs as $activity)
                                @php
                                    $changes = $activity->changes;
                                    $attributes = collect($changes->get('attributes', []));
                                    $oldValues = collect($changes->get('old', []));
                                    $fieldNames = $attributes->keys()->merge($oldValues->keys())->unique()->values();
                                    $event = strtolower((string) ($activity->event ?? $activity->description));
                                    $eventBadgeClass = $eventBadgeClasses[$event] ?? 'badge-info';
                                    $causerLabel = $resolveModelLabel($activity->causer, $activity->causer_type, $activity->causer_id);
                                    $subjectLabel = $resolveModelLabel($activity->subject, $activity->subject_type, $activity->subject_id);
                                    $rowNumber = $activityLogs->firstItem() + $loop->index;
                                    $properties = $activity->properties ?? collect();
                                @endphp
                                <tr>
                                    <td class="text-muted">{{ $rowNumber }}</td>
                                    <td class="activity-time">
                                        <div class="font-weight-bold">{{ optional($activity->created_at)->format('d M Y, h:i A') }}</div>
                                        <small class="text-muted">{{ optional($activity->created_at)->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ $causerLabel }}</div>
                                        <small class="text-muted">{{ class_basename((string) $activity->causer_type) ?: 'System' }} @if ($activity->causer_id) #{{ $activity->causer_id }} @endif</small>
                                    </td>
                                    <td><span class="badge {{ $eventBadgeClass }} activity-event-badge">{{ ucfirst($event ?: 'activity') }}</span></td>
                                    <td>
                                        <div class="font-weight-bold">{{ $subjectLabel }}</div>
                                        <small class="text-muted">{{ class_basename((string) $activity->subject_type) ?: 'No subject' }} @if ($activity->subject_id) #{{ $activity->subject_id }} @endif</small>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold">{{ ucfirst((string) $activity->description) }}</div>
                                        <small class="text-muted">Log: {{ $activity->log_name ?: 'default' }} @if ($fieldNames->isNotEmpty()) · {{ $fieldNames->count() }} field(s) captured @endif</small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary activity-view-button" type="button" data-toggle="collapse" data-target="#activity-log-{{ $activity->id }}" aria-expanded="false" aria-controls="activity-log-{{ $activity->id }}">
                                            <i class="mdi mdi-eye-outline mr-1"></i>View
                                        </button>
                                    </td>
                                </tr>
                                <tr class="activity-log-detail-row">
                                    <td colspan="7" class="p-0 border-0">
                                        <div class="collapse" id="activity-log-{{ $activity->id }}">
                                            <div class="activity-log-detail-panel">
                                                <div class="row">
                                                    <div class="col-xl-4 mb-3 mb-xl-0">
                                                        <div class="detail-card">
                                                            <h6 class="detail-title"><i class="mdi mdi-information-outline mr-1"></i>Metadata</h6>
                                                            <dl class="activity-meta-list mb-0">
                                                                <dt>Log Name</dt><dd>{{ $activity->log_name ?: 'default' }}</dd>
                                                                <dt>Event</dt><dd>{{ $activity->event ?: $activity->description }}</dd>
                                                                <dt>Batch UUID</dt><dd>{{ $activity->batch_uuid ?: '-' }}</dd>
                                                                <dt>Subject</dt><dd>{{ class_basename((string) $activity->subject_type) ?: '-' }} @if ($activity->subject_id) #{{ $activity->subject_id }} @endif</dd>
                                                                <dt>Causer</dt><dd>{{ class_basename((string) $activity->causer_type) ?: 'System' }} @if ($activity->causer_id) #{{ $activity->causer_id }} @endif</dd>
                                                                <dt>IP Address</dt><dd>{{ $properties->get('ip', '-') }}</dd>
                                                                <dt>Method</dt><dd>{{ $properties->get('method', '-') }}</dd>
                                                                <dt>URL</dt><dd class="activity-url">{{ $properties->get('url', '-') }}</dd>
                                                            </dl>
                                                        </div>
                                                    </div>
                                                    <div class="col-xl-8">
                                                        <div class="detail-card">
                                                            <h6 class="detail-title"><i class="mdi mdi-compare mr-1"></i>Change Summary</h6>
                                                            @if ($fieldNames->isNotEmpty())
                                                                <div class="table-responsive">
                                                                    <table class="table table-sm change-table mb-0">
                                                                        <thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>
                                                                        <tbody>
                                                                            @foreach ($fieldNames as $fieldName)
                                                                                <tr>
                                                                                    <td><code>{{ $fieldName }}</code></td>
                                                                                    <td><pre class="change-value old-value">{{ $formatValue($oldValues->get($fieldName)) }}</pre></td>
                                                                                    <td><pre class="change-value new-value">{{ $formatValue($attributes->get($fieldName)) }}</pre></td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            @else
                                                                <pre class="change-value mb-0">{{ $formatValue($properties->toArray()) }}</pre>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="activity-empty-state">No activity logs found for the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($activityLogs->hasPages())
                    <div class="activity-pagination">
                        <span>Showing {{ $activityLogs->firstItem() }} to {{ $activityLogs->lastItem() }} of {{ $activityLogs->total() }} logs</span>
                        {{ $activityLogs->links('pagination::bootstrap-4') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .activity-log-page { padding-top: 24px; padding-bottom: 30px; }
        .activity-summary-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; margin-bottom: 22px; }
        .activity-summary-card { border: 1px solid #e8edf3; border-radius: 12px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); margin-bottom: 0; }
        .activity-summary-card .card-body { position: relative; padding: 20px; }
        .summary-icon { position: absolute; right: 18px; top: 18px; display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center; border-radius: 10px; font-size: 20px; }
        .summary-icon-primary { color: #4c6fff; background: rgba(76, 111, 255, .12); }
        .summary-icon-info { color: #06a5c7; background: rgba(6, 165, 199, .12); }
        .summary-icon-success { color: #16a36a; background: rgba(22, 163, 106, .12); }
        .summary-icon-warning { color: #d58b00; background: rgba(213, 139, 0, .12); }
        .summary-icon-danger { color: #e34f5f; background: rgba(227, 79, 95, .12); }
        .summary-label { display: block; padding-right: 45px; margin-bottom: 12px; color: #6b778c; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .summary-value { margin-bottom: 4px; color: #17233c; font-size: 28px; font-weight: 700; }
        .summary-meta { color: #8490a3; font-size: 12px; }
        .activity-card { border: 0; border-radius: 12px; box-shadow: 0 10px 32px rgba(15, 23, 42, .06); overflow: hidden; }
        .activity-card-header { padding: 20px 24px; border-bottom: 1px solid #edf0f5; }
        .activity-filter-panel { padding: 18px 18px 2px; border: 1px solid #e4e9f0; border-radius: 10px; background: #f8fafc; }
        .activity-filter-panel label { color: #46546b; font-size: 12px; font-weight: 600; }
        .activity-filter-actions { display: flex; align-items: flex-end; padding-bottom: 16px; }
        .activity-log-table { min-width: 1050px; }
        .activity-log-table thead th { padding: 13px 12px; border-top: 0; border-bottom: 1px solid #dee4ec; background: #f7f9fc; color: #536079; font-size: 11px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
        .activity-log-table tbody > tr:not(.activity-log-detail-row) > td { padding: 14px 12px; border-top: 1px solid #eef1f5; vertical-align: middle; }
        .activity-time { min-width: 175px; }
        .activity-event-badge { min-width: 70px; padding: 7px 10px; border-radius: 20px; font-size: 10px; letter-spacing: .04em; text-transform: uppercase; }
        .activity-view-button { border-radius: 7px; white-space: nowrap; }
        .activity-log-detail-panel { padding: 18px; border-top: 1px solid #e8edf3; background: #f5f8fc; }
        .detail-card { height: 100%; padding: 18px; border: 1px solid #e1e7ef; border-radius: 10px; background: #fff; }
        .detail-title { margin-bottom: 15px; color: #26344d; font-weight: 700; }
        .activity-meta-list { display: grid; grid-template-columns: 105px minmax(0, 1fr); gap: 9px 12px; font-size: 12px; }
        .activity-meta-list dt { margin: 0; color: #6c778b; font-weight: 600; }
        .activity-meta-list dd { margin: 0; color: #202d45; overflow-wrap: anywhere; }
        .activity-url { max-width: 250px; }
        .change-table th { background: #f8fafc; color: #667085; font-size: 11px; text-transform: uppercase; }
        .change-table td { min-width: 170px; vertical-align: top; }
        .change-value { margin: 0; padding: 10px; border-radius: 7px; background: #f8fafc; color: #25324a; font-family: Consolas, monospace; font-size: 11px; line-height: 1.5; white-space: pre-wrap; overflow-wrap: anywhere; }
        .old-value { border: 1px solid rgba(227, 79, 95, .2); background: rgba(227, 79, 95, .035); }
        .new-value { border: 1px solid rgba(22, 163, 106, .2); background: rgba(22, 163, 106, .035); }
        .activity-empty-state { padding: 55px 20px !important; color: #7a879b; text-align: center; }
        .activity-pagination { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; margin-top: 22px; color: #7a879b; font-size: 12px; }
        .activity-pagination .pagination { margin-bottom: 0; }
        @media (max-width: 1199.98px) { .activity-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 767.98px) {
            .activity-summary-grid { grid-template-columns: 1fr; }
            .activity-pagination { gap: 12px; }
            .activity-meta-list { grid-template-columns: 1fr; gap: 3px; }
            .activity-meta-list dd { margin-bottom: 7px; }
        }
    </style>
@endpush
