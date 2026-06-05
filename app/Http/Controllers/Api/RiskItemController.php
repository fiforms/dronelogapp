<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RiskItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->currentTeam()
            ->riskItems()
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $request->user()->currentTeam()->riskItems()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function update(Request $request, RiskItem $riskItem): JsonResponse
    {
        abort_unless($request->user()->currentTeam()->id === $riskItem->team_id, 403);

        $riskItem->update($request->only(['label', 'description', 'sort_order', 'is_active']));

        return response()->json(['data' => $riskItem]);
    }

    public function destroy(Request $request, RiskItem $riskItem): JsonResponse
    {
        abort_unless($request->user()->currentTeam()->id === $riskItem->team_id, 403);

        $riskItem->update(['is_active' => false]);

        return response()->json(null, 204);
    }
}
