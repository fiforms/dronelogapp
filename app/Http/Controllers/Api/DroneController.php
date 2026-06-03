<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDroneRequest;
use App\Http\Requests\UpdateDroneRequest;
use App\Http\Resources\DroneResource;
use App\Models\Drone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DroneController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $drones = $request->user()->currentTeam()->drones()->orderBy('name')->get();

        return DroneResource::collection($drones);
    }

    public function store(StoreDroneRequest $request): DroneResource
    {
        $drone = $request->user()->currentTeam()->drones()->create($request->validated());

        return new DroneResource($drone);
    }

    public function show(Request $request, Drone $drone): DroneResource
    {
        $this->authorizeTeam($request, $drone->team_id);

        return new DroneResource($drone);
    }

    public function update(UpdateDroneRequest $request, Drone $drone): DroneResource
    {
        $this->authorizeTeam($request, $drone->team_id);
        $drone->update($request->validated());

        return new DroneResource($drone);
    }

    public function destroy(Request $request, Drone $drone): JsonResponse
    {
        $this->authorizeTeam($request, $drone->team_id);
        $drone->delete();

        return response()->json(null, 204);
    }

    private function authorizeTeam(Request $request, int $teamId): void
    {
        abort_unless($request->user()->currentTeam()->id === $teamId, 403);
    }
}
