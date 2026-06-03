<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChecklistItemRequest;
use App\Http\Resources\ChecklistItemResource;
use App\Models\ChecklistItem;
use App\Models\ChecklistTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChecklistItemController extends Controller
{
    public function index(Request $request, ChecklistTemplate $checklistTemplate): AnonymousResourceCollection
    {
        abort_unless($request->user()->currentTeam()->id === $checklistTemplate->team_id, 403);

        return ChecklistItemResource::collection($checklistTemplate->items);
    }

    public function store(StoreChecklistItemRequest $request, ChecklistTemplate $checklistTemplate): ChecklistItemResource
    {
        abort_unless($request->user()->currentTeam()->id === $checklistTemplate->team_id, 403);

        $item = $checklistTemplate->items()->create($request->validated());

        return new ChecklistItemResource($item);
    }

    public function show(ChecklistItem $checklistItem): ChecklistItemResource
    {
        return new ChecklistItemResource($checklistItem);
    }

    public function update(Request $request, ChecklistItem $checklistItem): ChecklistItemResource
    {
        $checklistItem->update($request->only(['label', 'sort_order', 'has_comment_box']));

        return new ChecklistItemResource($checklistItem);
    }

    public function destroy(ChecklistItem $checklistItem): JsonResponse
    {
        $checklistItem->delete();

        return response()->json(null, 204);
    }
}
