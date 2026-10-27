<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = Activity::query()
                ->with('causer')
                ->select('activity_log.*');

            return DataTables::eloquent($query)

                ->addColumn('user', function ($row) {
                    return $row->causer?->name ?? 'System';
                })

                ->addColumn('activity', function ($row) {
                    return ucfirst($row->event ?? 'activity');
                })

                ->addColumn('module', function ($row) {
                    return ucfirst($row->log_name ?? 'default');
                })

                ->addColumn('subject', function ($row) {
                    if (! $row->subject_type || ! $row->subject_id) {
                        return '-';
                    }

                    return class_basename($row->subject_type)
                        .' #'.$row->subject_id;
                })

                ->addColumn('url', function ($row) {
                    return $row->properties->get('url', '-');
                })

                ->addColumn('ip_address', function ($row) {
                    return $row->properties->get('ip', '-');
                })

                ->addColumn('device_browser', function ($row) {
                    return $row->properties->get('user_agent', '-');
                })

                ->editColumn('description', function ($row) {
                    return $row->description;
                })

                ->editColumn('created_at', function ($row) {
                    return $row->created_at
                        ? $row->created_at->format('d M Y, h:i A')
                        : '-';
                })

                ->rawColumns(['details'])
                ->make(true);
        }

        return view('admin.activity-log.index');
    }

    public function show(Activity $activity)
    {
        $activity->load('causer');

        return response()->json([
            'id' => $activity->id,
            'user' => $activity->causer?->name ?? 'System',
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject' => $activity->subject_type
                ? class_basename($activity->subject_type)
                .' #'.($activity->subject_id ?? '-')
                : '-',
            'properties' => $activity->properties->toArray(),
            'created_at' => $activity->created_at?->format('d M Y, h:i A'),
        ]);
    }
}
