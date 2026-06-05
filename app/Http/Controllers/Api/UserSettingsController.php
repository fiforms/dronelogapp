<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'risk_threshold' => $request->user()->risk_threshold,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'risk_threshold' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $request->user()->update($data);

        return response()->json([
            'risk_threshold' => $request->user()->risk_threshold,
        ]);
    }
}
