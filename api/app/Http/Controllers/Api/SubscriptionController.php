<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscriptions\StoreOutageSubscriptionRequest;
use App\Http\Resources\OutageSubscriptionResource;
use App\Models\OutageSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subscriptions = $request->user()
            ->outageSubscriptions()
            ->withCoordinates()
            ->latest()
            ->get();

        return OutageSubscriptionResource::collection($subscriptions);
    }

    public function store(StoreOutageSubscriptionRequest $request): JsonResponse
    {
        $subscription = OutageSubscription::createWithCoordinates([
            'user_id' => $request->user()->id,
            'radius_km' => $request->validated('radius_km'),
        ], $request->validated('latitude'), $request->validated('longitude'));

        return (new OutageSubscriptionResource(
            OutageSubscription::query()->withCoordinates()->findOrFail($subscription->id)
        ))->response()->setStatusCode(201);
    }

    public function destroy(OutageSubscription $subscription): JsonResponse
    {
        $this->authorize('delete', $subscription);

        $subscription->delete();

        return response()->json(status: 204);
    }
}
