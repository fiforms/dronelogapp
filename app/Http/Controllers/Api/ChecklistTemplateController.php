<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChecklistTemplateRequest;
use App\Http\Resources\ChecklistTemplateResource;
use App\Models\ChecklistTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChecklistTemplateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = $request->user()->currentTeam()
            ->checklistTemplates()
            ->with('items')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return ChecklistTemplateResource::collection($templates);
    }

    public function store(StoreChecklistTemplateRequest $request): ChecklistTemplateResource
    {
        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $template = $request->user()->currentTeam()->checklistTemplates()->create($data);

        foreach ($items as $i => $item) {
            $template->items()->create(array_merge($item, ['sort_order' => $item['sort_order'] ?? $i + 1]));
        }

        return new ChecklistTemplateResource($template->load('items'));
    }

    public function show(Request $request, ChecklistTemplate $checklistTemplate): ChecklistTemplateResource
    {
        $this->authorizeTeam($request, $checklistTemplate->team_id);

        return new ChecklistTemplateResource($checklistTemplate->load('items'));
    }

    public function update(Request $request, ChecklistTemplate $checklistTemplate): ChecklistTemplateResource
    {
        $this->authorizeTeam($request, $checklistTemplate->team_id);

        $checklistTemplate->update($request->only(['name', 'is_default']));

        return new ChecklistTemplateResource($checklistTemplate->load('items'));
    }

    public function destroy(Request $request, ChecklistTemplate $checklistTemplate): JsonResponse
    {
        $this->authorizeTeam($request, $checklistTemplate->team_id);
        $checklistTemplate->delete();

        return response()->json(null, 204);
    }

    private function authorizeTeam(Request $request, int $teamId): void
    {
        abort_unless($request->user()->currentTeam()->id === $teamId, 403);
    }
}
