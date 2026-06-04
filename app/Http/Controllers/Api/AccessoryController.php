<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccessoryRequest;
use App\Http\Requests\UpdateAccessoryRequest;
use App\Http\Resources\AccessoryResource;
use App\Models\Accessory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccessoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $accessories = $request->user()->currentTeam()->accessories()
            ->withCount('flights')
            ->orderBy('name')
            ->get();

        return AccessoryResource::collection($accessories);
    }

    public function store(StoreAccessoryRequest $request): AccessoryResource
    {
        $accessory = $request->user()->currentTeam()->accessories()->create($request->validated());

        return new AccessoryResource($accessory);
    }

    public function show(Request $request, Accessory $accessory): AccessoryResource
    {
        $this->authorizeTeam($request, $accessory->team_id);

        return new AccessoryResource($accessory);
    }

    public function update(UpdateAccessoryRequest $request, Accessory $accessory): AccessoryResource
    {
        $this->authorizeTeam($request, $accessory->team_id);
        $accessory->update($request->validated());

        return new AccessoryResource($accessory);
    }

    public function destroy(Request $request, Accessory $accessory): JsonResponse
    {
        $this->authorizeTeam($request, $accessory->team_id);

        if ($accessory->flights()->exists()) {
            return response()->json(
                ['message' => 'This accessory has flight records and cannot be deleted. Deactivate it instead.'],
                409
            );
        }

        $accessory->delete();

        return response()->json(null, 204);
    }

    private function authorizeTeam(Request $request, int $teamId): void
    {
        abort_unless($request->user()->currentTeam()->id === $teamId, 403);
    }
}
