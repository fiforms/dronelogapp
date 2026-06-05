<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    /**
     * Bulk upsert flights from the client. Uses client_uuid for deduplication.
     * Safe to call multiple times with the same payload.
     */
    public function flights(Request $request): JsonResponse
    {
        $request->validate([
            'flights'                               => ['required', 'array'],
            'flights.*.client_uuid'                 => ['required', 'uuid'],
            'flights.*.drone_id'                    => ['nullable', 'integer'],
            'flights.*.battery_id'                  => ['nullable', 'integer'],
            'flights.*.battery_pct_start'           => ['nullable', 'integer'],
            'flights.*.battery_pct_end'             => ['nullable', 'integer'],
            'flights.*.started_at'                  => ['required', 'date'],
            'flights.*.ended_at'                    => ['nullable', 'date'],
            'flights.*.duration_minutes'            => ['nullable', 'integer'],
            'flights.*.lat'                         => ['nullable', 'numeric'],
            'flights.*.lng'                         => ['nullable', 'numeric'],
            'flights.*.location_description'        => ['nullable', 'string'],
            'flights.*.flight_plan'                 => ['nullable', 'string'],
            'flights.*.purpose'                     => ['required', 'in:recreational,commercial'],
            'flights.*.purpose_notes'               => ['nullable', 'string'],
            'flights.*.laanc_status'                => ['required', 'in:received,not_needed,na'],
            'flights.*.laanc_authorization_number'  => ['nullable', 'string'],
            'flights.*.post_flight_notes'           => ['nullable', 'string'],
            'flights.*.is_retrospective'            => ['nullable', 'boolean'],
            'flights.*.accessories'                 => ['nullable', 'array'],
            'flights.*.checklist'                   => ['nullable', 'array'],
        ]);

        $team    = $request->user()->currentTeam();
        $userId  = $request->user()->id;
        $synced  = 0;
        $ids     = [];
        $errors  = [];

        foreach ($request->input('flights') as $payload) {
            try {
                $accessories = $payload['accessories'] ?? [];
                $checklist   = $payload['checklist'] ?? [];
                unset($payload['accessories'], $payload['checklist']);

                $flight = Flight::updateOrCreate(
                    ['client_uuid' => $payload['client_uuid']],
                    array_merge($payload, [
                        'team_id'   => $team->id,
                        'user_id'   => $userId,
                        'synced_at' => now(),
                    ])
                );

                if ($accessories) {
                    $flight->accessories()->sync($accessories);
                }

                foreach ($checklist as $entry) {
                    $flight->checklistEntries()->updateOrCreate(
                        ['checklist_item_id' => $entry['checklist_item_id']],
                        ['checked' => $entry['checked'], 'comment' => $entry['comment'] ?? null]
                    );
                }

                $ids[$payload['client_uuid']] = $flight->id;
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = ['client_uuid' => $payload['client_uuid'], 'error' => $e->getMessage()];
            }
        }

        return response()->json(['synced' => $synced, 'ids' => $ids, 'errors' => $errors]);
    }

    /** Returns current server time and number of unsynced flights for this user's team. */
    public function status(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam();

        return response()->json([
            'server_time' => now()->toIso8601String(),
            'flight_count' => $team->flights()->count(),
        ]);
    }
}
