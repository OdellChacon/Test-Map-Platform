<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\PublicReportIndexRequest;
use App\Http\Resources\PublicOutageReportResource;
use App\Models\OutageReport;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PublicReportController extends Controller
{
    public function index(PublicReportIndexRequest $request): AnonymousResourceCollection
    {
        $reports = OutageReport::query()
            ->active()
            ->withCoordinates()
            ->withCount('confirmations')
            ->withinBoundingBox(
                $request->validated('south_west_lat'),
                $request->validated('south_west_lng'),
                $request->validated('north_east_lat'),
                $request->validated('north_east_lng'),
            )
            ->get();

        return PublicOutageReportResource::collection($reports);
    }
}
