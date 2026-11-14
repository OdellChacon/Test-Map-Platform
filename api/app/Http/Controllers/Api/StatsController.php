<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\OutageReport;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        $since = now()->subDays(14)->startOfDay();

        $perDay = OutageReport::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count])
            ->values();

        $averageDurationMinutes = OutageReport::query()
            ->whereIn('status', [ReportStatus::Closed, ReportStatus::Expired])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (COALESCE(closed_at, expires_at) - created_at)) / 60) as avg_minutes')
            ->value('avg_minutes');

        return response()->json([
            'data' => [
                'reports_per_day' => $perDay,
                'average_duration_minutes' => $averageDurationMinutes !== null ? round((float) $averageDurationMinutes, 1) : null,
                'total_reports' => OutageReport::query()->count(),
                'active_reports' => OutageReport::query()->active()->count(),
            ],
        ]);
    }
}
