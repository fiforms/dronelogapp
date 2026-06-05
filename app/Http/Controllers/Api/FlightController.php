<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFlightRequest;
use App\Http\Requests\UpdateFlightRequest;
use App\Http\Resources\FlightResource;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class FlightController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $team = $request->user()->currentTeam();

        $query = $team->flights()
            ->with(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem', 'riskScores'])
            ->orderByDesc('started_at');

        if ($request->has('from')) {
            $query->where('started_at', '>=', $request->date('from'));
        }

        if ($request->has('to')) {
            $query->where('started_at', '<=', $request->date('to'));
        }

        $flights = $query->paginate($request->integer('per_page', 50));

        return FlightResource::collection($flights);
    }

    public function store(StoreFlightRequest $request): JsonResponse
    {
        $team = $request->user()->currentTeam();

        // Return existing record if client_uuid already exists (idempotent)
        $existing = $team->flights()->where('client_uuid', $request->client_uuid)->first();
        if ($existing) {
            return (new FlightResource($existing->load(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem'])))
                ->response()->setStatusCode(200);
        }

        $data = $request->validated();
        $accessories = $data['accessories'] ?? [];
        $checklist = $data['checklist'] ?? [];
        unset($data['accessories'], $data['checklist']);

        $flight = $team->flights()->create(array_merge($data, [
            'user_id'   => $request->user()->id,
            'synced_at' => now(),
        ]));

        if ($accessories) {
            $flight->accessories()->sync($accessories);
        }

        foreach ($checklist as $entry) {
            $flight->checklistEntries()->create($entry);
        }

        return (new FlightResource($flight->load(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem'])))
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, Flight $flight): FlightResource
    {
        $this->authorizeTeam($request, $flight->team_id);

        return new FlightResource($flight->load(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem']));
    }

    public function update(UpdateFlightRequest $request, Flight $flight): FlightResource
    {
        $this->authorizeTeam($request, $flight->team_id);

        $data = $request->validated();
        $accessories = $data['accessories'] ?? null;
        $checklist = $data['checklist'] ?? null;
        unset($data['accessories'], $data['checklist']);

        $flight->update($data);

        if ($accessories !== null) {
            $flight->accessories()->sync($accessories);
        }

        if ($checklist !== null) {
            foreach ($checklist as $entry) {
                $flight->checklistEntries()->updateOrCreate(
                    ['checklist_item_id' => $entry['checklist_item_id']],
                    ['checked' => $entry['checked'], 'comment' => $entry['comment'] ?? null]
                );
            }
        }

        return new FlightResource($flight->load(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem']));
    }

    public function destroy(Request $request, Flight $flight): JsonResponse
    {
        $this->authorizeTeam($request, $flight->team_id);
        $flight->delete();

        return response()->json(null, 204);
    }

    /** Mark a flight as ended (sets ended_at). */
    public function end(Request $request, Flight $flight): FlightResource
    {
        $this->authorizeTeam($request, $flight->team_id);

        $flight->update([
            'ended_at'          => $request->date('ended_at') ?? now(),
            'post_flight_notes' => $request->input('post_flight_notes'),
        ]);

        return new FlightResource($flight->load(['drone', 'battery', 'accessories', 'checklistEntries.checklistItem']));
    }

    private function authorizeTeam(Request $request, int $teamId): void
    {
        abort_unless($request->user()->currentTeam()->id === $teamId, 403);
    }
}
