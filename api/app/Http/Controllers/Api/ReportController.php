<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportDurationType;
use App\Enums\ReportStatus;
use App\Events\PublicOutageReportChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\StoreOutageReportRequest;
use App\Http\Requests\Reports\UpdateOutageReportRequest;
use App\Http\Resources\OutageReportResource;
use App\Jobs\NotifySubscribersOfNewOutage;
use App\Jobs\ResolveOutageReportAddress;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $reports = $request->user()
            ->outageReports()
            ->withCoordinates()
            ->withCount('confirmations')
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
        $latitude = $request->validated('latitude');
        $longitude = $request->validated('longitude');
        $radiusKm = $request->validated('radius_km');

        $report = DB::transaction(function () use ($request, $durationType, $durationHours, $expiresAt, $latitude, $longitude, $radiusKm) {
            User::query()->whereKey($request->user()->id)->lockForUpdate()->first();

            $activeReports = $request->user()->outageReports()->active()->count();

            if ($activeReports >= config('outage.max_active_reports_per_user')) {
                throw ValidationException::withMessages([
                    'reports' => ['You have reached the maximum number of active outage reports allowed.'],
                ]);
            }

            return OutageReport::createWithCoordinates([
                'user_id' => $request->user()->id,
                'radius_km' => $radiusKm,
                'duration_type' => $durationType,
                'duration_hours' => $durationHours,
                'expires_at' => $expiresAt,
                'status' => ReportStatus::Active,
                'description' => $request->validated('description'),
            ], $latitude, $longitude);
        });

        ResolveOutageReportAddress::dispatch($report->id, $latitude, $longitude);
        NotifySubscribersOfNewOutage::dispatch($report->id, $latitude, $longitude, (float) $radiusKm, $request->user()->id);

        PublicOutageReportChanged::dispatch('created', $report->id, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius_km' => (float) $radiusKm,
            'duration_type' => $durationType->value,
            'description' => $report->description,
            'address' => null,
            'expires_at' => $expiresAt->toIso8601String(),
            'created_at' => $report->created_at->toIso8601String(),
            'confirmations_count' => 0,
        ]);

        return (new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        ))->response()->setStatusCode(201);
    }

    public function update(UpdateOutageReportRequest $request, OutageReport $report): OutageReportResource
    {
        $this->authorize('update', $report);

        if ($report->effective_status !== ReportStatus::Active) {
            throw ValidationException::withMessages([
                'report' => ['Only active reports can be edited.'],
            ]);
        }

        $attributes = $request->validated();

        if (array_key_exists('duration_hours', $attributes)) {
            $report->expires_at = $report->created_at->clone()->addHours($attributes['duration_hours']);
        }

        $report->fill($attributes)->save();

        $report = OutageReport::query()->withCoordinates()->findOrFail($report->id);

        PublicOutageReportChanged::dispatch('updated', $report->id, [
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'radius_km' => (float) $report->radius_km,
            'duration_type' => $report->duration_type->value,
            'description' => $report->description,
            'address' => $report->address,
            'expires_at' => $report->expires_at->toIso8601String(),
            'created_at' => $report->created_at->toIso8601String(),
            'confirmations_count' => $report->confirmations()->count(),
        ]);

        return new OutageReportResource($report);
    }

    public function close(OutageReport $report): OutageReportResource
    {
        $this->authorize('close', $report);

        if ($report->effective_status !== ReportStatus::Active) {
            throw ValidationException::withMessages([
                'report' => ['Only active reports can be closed.'],
            ]);
        }

        $report->update([
            'status' => ReportStatus::Closed,
            'closed_at' => now(),
        ]);

        PublicOutageReportChanged::dispatch('removed', $report->id);

        return new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        );
    }

    public function confirm(Request $request, OutageReport $report): JsonResponse
    {
        $this->authorize('confirm', $report);

        if ($report->effective_status !== ReportStatus::Active) {
            throw ValidationException::withMessages([
                'report' => ['Only active reports can be confirmed.'],
            ]);
        }

        $alreadyConfirmed = $report->confirmations()
            ->where('user_id', $request->user()->id)
            ->exists();

        if ($alreadyConfirmed) {
            throw ValidationException::withMessages([
                'confirmation' => ['You have already confirmed this report.'],
            ]);
        }

        try {
            $report->confirmations()->create(['user_id' => $request->user()->id]);
        } catch (QueryException $exception) {
            $stillConfirmed = $report->confirmations()
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $stillConfirmed) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'confirmation' => ['You have already confirmed this report.'],
            ]);
        }

        $confirmationsCount = $report->confirmations()->count();
        $report = OutageReport::query()->withCoordinates()->findOrFail($report->id);

        PublicOutageReportChanged::dispatch('updated', $report->id, [
            'latitude' => $report->latitude,
            'longitude' => $report->longitude,
            'radius_km' => (float) $report->radius_km,
            'duration_type' => $report->duration_type->value,
            'description' => $report->description,
            'address' => $report->address,
            'expires_at' => $report->expires_at->toIso8601String(),
            'created_at' => $report->created_at->toIso8601String(),
            'confirmations_count' => $confirmationsCount,
        ]);

        return response()->json(['data' => ['confirmations_count' => $confirmationsCount]]);
    }
}
