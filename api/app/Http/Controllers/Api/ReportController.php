<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportDurationType;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreOutageReportRequest;
use App\Http\Requests\Reports\UpdateOutageReportRequest;
use App\Http\Resources\OutageReportResource;
use App\Models\OutageReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reports = $request->user()
            ->outageReports()
            ->withCoordinates()
            ->latest()
            ->paginate(15);

        return OutageReportResource::collection($reports);
    }

    public function store(StoreOutageReportRequest $request): JsonResponse
    {
        $durationType = ReportDurationType::from($request->validated('duration_type'));
        $durationHours = $durationType === ReportDurationType::Fixed
            ? $request->validated('duration_hours')
            : null;
        $expiresAt = now()->addHours(
            $durationType === ReportDurationType::Fixed
                ? $durationHours
                : config('outage.max_report_duration_hours')
        );

        $report = OutageReport::createWithCoordinates([
            'user_id' => $request->user()->id,
            'radius_km' => $request->validated('radius_km'),
            'duration_type' => $durationType,
            'duration_hours' => $durationHours,
            'expires_at' => $expiresAt,
            'status' => ReportStatus::Active,
            'description' => $request->validated('description'),
        ], $request->validated('latitude'), $request->validated('longitude'));

        return (new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateOutageReportRequest $request, OutageReport $report): OutageReportResource
    {
        $this->authorize('update', $report);

        if ($report->status !== ReportStatus::Active) {
            abort(422, 'Only active reports can be edited.');
        }

        $attributes = $request->validated();

        if (array_key_exists('duration_hours', $attributes)) {
            $report->expires_at = $report->created_at->clone()->addHours($attributes['duration_hours']);
        }

        $report->fill($attributes)->save();

        return new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        );
    }

    public function close(OutageReport $report): OutageReportResource
    {
        $this->authorize('close', $report);

        if ($report->status !== ReportStatus::Active) {
            abort(422, 'Only active reports can be closed.');
        }

        $report->update([
            'status' => ReportStatus::Closed,
            'closed_at' => now(),
        ]);

        return new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        );
    }
}
