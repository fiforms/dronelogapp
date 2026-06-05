<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function export(Request $request): JsonResponse
    {
        $team = $request->user()->currentTeam();

        $drones      = $team->drones()->get(['id', 'name', 'model', 'serial', 'registration_number', 'notes', 'is_active']);
        $batteries   = $team->batteries()->get(['id', 'name', 'capacity_mah', 'purchase_date', 'notes', 'is_active']);
        $accessories = $team->accessories()->get(['id', 'name', 'type', 'notes', 'is_active']);

        $templates = $team->checklistTemplates()->with('items')->get()->map(fn ($t) => [
            'id'         => $t->id,
            'name'       => $t->name,
            'is_default' => $t->is_default,
            'items'      => $t->items->map(fn ($i) => [
                'id'              => $i->id,
                'sort_order'      => $i->sort_order,
                'label'           => $i->label,
                'has_comment_box' => $i->has_comment_box,
            ])->values()->all(),
        ])->values()->all();

        $flights = $team->flights()
            ->with(['accessories', 'checklistEntries'])
            ->orderBy('started_at')
            ->get()
            ->map(fn ($f) => [
                'client_uuid'                => $f->client_uuid,
                'drone_id'                   => $f->drone_id,
                'battery_id'                 => $f->battery_id,
                'battery_pct_start'          => $f->battery_pct_start,
                'battery_pct_end'            => $f->battery_pct_end,
                'started_at'                 => $f->started_at?->toIso8601String(),
                'ended_at'                   => $f->ended_at?->toIso8601String(),
                'duration_minutes'           => $f->duration_minutes,
                'lat'                        => $f->lat,
                'lng'                        => $f->lng,
                'location_description'       => $f->location_description,
                'flight_plan'                => $f->flight_plan,
                'purpose'                    => $f->purpose?->value,
                'purpose_notes'              => $f->purpose_notes,
                'laanc_status'               => $f->laanc_status?->value,
                'laanc_authorization_number' => $f->laanc_authorization_number,
                'post_flight_notes'          => $f->post_flight_notes,
                'is_retrospective'           => (bool) $f->is_retrospective,
                'accessories'                => $f->accessories->pluck('id')->values()->all(),
                'checklist'                  => $f->checklistEntries->map(fn ($e) => [
                    'checklist_item_id' => $e->checklist_item_id,
                    'checked'           => (bool) $e->checked,
                    'comment'           => $e->comment,
                ])->values()->all(),
            ])->values()->all();

        return response()->json([
            'version'             => 1,
            'exported_at'         => now()->toIso8601String(),
            'drones'              => $drones,
            'batteries'           => $batteries,
            'accessories'         => $accessories,
            'checklist_templates' => $templates,
            'flights'             => $flights,
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'version'             => ['required', 'integer'],
            'drones'              => ['nullable', 'array'],
            'batteries'           => ['nullable', 'array'],
            'accessories'         => ['nullable', 'array'],
            'checklist_templates' => ['nullable', 'array'],
            'flights'             => ['nullable', 'array'],
        ]);

        $team   = $request->user()->currentTeam();
        $userId = $request->user()->id;

        $droneMap   = [];
        $batteryMap = [];
        $accessMap  = [];
        $itemMap    = [];

        foreach ($request->input('drones', []) as $d) {
            $drone              = $team->drones()->create([
                'name'                => $d['name'],
                'model'               => $d['model'] ?? null,
                'serial'              => $d['serial'] ?? null,
                'registration_number' => $d['registration_number'] ?? null,
                'notes'               => $d['notes'] ?? null,
                'is_active'           => $d['is_active'] ?? true,
            ]);
            $droneMap[$d['id']] = $drone->id;
        }

        foreach ($request->input('batteries', []) as $b) {
            $battery              = $team->batteries()->create([
                'name'          => $b['name'],
                'capacity_mah'  => $b['capacity_mah'] ?? null,
                'purchase_date' => $b['purchase_date'] ?? null,
                'notes'         => $b['notes'] ?? null,
                'is_active'     => $b['is_active'] ?? true,
            ]);
            $batteryMap[$b['id']] = $battery->id;
        }

        foreach ($request->input('accessories', []) as $a) {
            $accessory            = $team->accessories()->create([
                'name'      => $a['name'],
                'type'      => $a['type'] ?? null,
                'notes'     => $a['notes'] ?? null,
                'is_active' => $a['is_active'] ?? true,
            ]);
            $accessMap[$a['id']] = $accessory->id;
        }

        foreach ($request->input('checklist_templates', []) as $t) {
            $template = $team->checklistTemplates()->create([
                'name'       => $t['name'],
                'is_default' => $t['is_default'] ?? false,
            ]);
            foreach ($t['items'] ?? [] as $item) {
                $newItem             = $template->items()->create([
                    'sort_order'      => $item['sort_order'],
                    'label'           => $item['label'],
                    'has_comment_box' => $item['has_comment_box'] ?? false,
                ]);
                $itemMap[$item['id']] = $newItem->id;
            }
        }

        $synced = 0;
        $errors = [];

        foreach ($request->input('flights', []) as $f) {
            try {
                $droneId   = isset($f['drone_id'])   ? ($droneMap[$f['drone_id']]     ?? null) : null;
                $batteryId = isset($f['battery_id']) ? ($batteryMap[$f['battery_id']] ?? null) : null;

                $accessories = array_values(array_filter(
                    array_map(fn ($id) => $accessMap[$id] ?? null, $f['accessories'] ?? [])
                ));

                $checklist = array_values(array_filter(
                    array_map(function ($entry) use ($itemMap) {
                        $newId = $itemMap[$entry['checklist_item_id']] ?? null;
                        if ($newId === null) {
                            return null;
                        }

                        return [
                            'checklist_item_id' => $newId,
                            'checked'           => $entry['checked'],
                            'comment'           => $entry['comment'] ?? null,
                        ];
                    }, $f['checklist'] ?? [])
                ));

                $flight = Flight::updateOrCreate(
                    ['client_uuid' => $f['client_uuid']],
                    [
                        'team_id'                    => $team->id,
                        'user_id'                    => $userId,
                        'drone_id'                   => $droneId,
                        'battery_id'                 => $batteryId,
                        'battery_pct_start'          => $f['battery_pct_start'] ?? null,
                        'battery_pct_end'            => $f['battery_pct_end'] ?? null,
                        'started_at'                 => $f['started_at'],
                        'ended_at'                   => $f['ended_at'] ?? null,
                        'duration_minutes'           => $f['duration_minutes'] ?? null,
                        'lat'                        => $f['lat'] ?? null,
                        'lng'                        => $f['lng'] ?? null,
                        'location_description'       => $f['location_description'] ?? null,
                        'flight_plan'                => $f['flight_plan'] ?? null,
                        'purpose'                    => $f['purpose'],
                        'purpose_notes'              => $f['purpose_notes'] ?? null,
                        'laanc_status'               => $f['laanc_status'],
                        'laanc_authorization_number' => $f['laanc_authorization_number'] ?? null,
                        'post_flight_notes'          => $f['post_flight_notes'] ?? null,
                        'is_retrospective'           => $f['is_retrospective'] ?? false,
                        'synced_at'                  => now(),
                    ]
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

                $synced++;
            } catch (\Throwable $e) {
                $errors[] = ['client_uuid' => $f['client_uuid'] ?? null, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'drones_created'      => count($droneMap),
            'batteries_created'   => count($batteryMap),
            'accessories_created' => count($accessMap),
            'templates_created'   => count($request->input('checklist_templates', [])),
            'flights_synced'      => $synced,
            'errors'              => $errors,
        ]);
    }
}
