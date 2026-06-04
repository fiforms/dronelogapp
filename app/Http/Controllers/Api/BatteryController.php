<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBatteryRequest;
use App\Http\Requests\UpdateBatteryRequest;
use App\Http\Resources\BatteryResource;
use App\Models\Battery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BatteryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $batteries = $request->user()->currentTeam()->batteries()
            ->withCount('flights')
            ->orderBy('name')
            ->get();

        return BatteryResource::collection($batteries);
    }

    public function store(StoreBatteryRequest $request): BatteryResource
    {
        $battery = $request->user()->currentTeam()->batteries()->create($request->validated());

        return new BatteryResource($battery);
    }

    public function show(Request $request, Battery $battery): BatteryResource
    {
        $this->authorizeTeam($request, $battery->team_id);

        return new BatteryResource($battery);
    }

    public function update(UpdateBatteryRequest $request, Battery $battery): BatteryResource
    {
        $this->authorizeTeam($request, $battery->team_id);
        $battery->update($request->validated());

        return new BatteryResource($battery);
    }

    public function destroy(Request $request, Battery $battery): JsonResponse
    {
        $this->authorizeTeam($request, $battery->team_id);

        if ($battery->flights()->exists()) {
            return response()->json(
                ['message' => 'This battery has flight records and cannot be deleted. Deactivate it instead.'],
                409
            );
        }

        $battery->delete();

        return response()->json(null, 204);
    }

    private function authorizeTeam(Request $request, int $teamId): void
    {
        abort_unless($request->user()->currentTeam()->id === $teamId, 403);
    }
}
