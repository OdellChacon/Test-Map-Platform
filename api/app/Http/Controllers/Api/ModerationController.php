<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReportStatus;
use App\Events\PublicOutageReportChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\ModerateReportRequest;
use App\Http\Resources\OutageReportResource;
use App\Models\OutageReport;
use App\Models\ReportModerationAction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModerationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if (! $request->user()->is_moderator) {
            abort(403);
        }

        $reports = OutageReport::query()
            ->active()
            ->withCoordinates()
            ->withCount('confirmations')
            ->latest()
            ->paginate(15);

        return OutageReportResource::collection($reports);
    }

    public function store(ModerateReportRequest $request, OutageReport $report): OutageReportResource
    {
        $this->authorize('moderate', $report);

        if ($report->status !== ReportStatus::Active) {
            abort(422, 'Only active reports can be moderated.');
        }

        $action = $request->validated('action');

        $report->update([
            'status' => $action === 'hide' ? ReportStatus::Hidden : ReportStatus::Closed,
            'closed_at' => now(),
        ]);

        ReportModerationAction::query()->create([
            'outage_report_id' => $report->id,
            'moderator_id' => $request->user()->id,
            'action' => $action,
            'reason' => $request->validated('reason'),
        ]);

        PublicOutageReportChanged::dispatch('removed', $report->id);

        return new OutageReportResource(
            OutageReport::query()->withCoordinates()->findOrFail($report->id)
        );
    }
}
