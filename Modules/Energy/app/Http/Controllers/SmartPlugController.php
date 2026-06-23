<?php

namespace Modules\Energy\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Energy\Models\EnergyReading;

class SmartPlugController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $apiKey = $request->bearerToken();
        $validKey = config('services.smartplug.api_key');

        if (! $validKey || $apiKey !== $validKey) {
            return response()->json(['error' => __('messages.unauthorized')], 401);
        }

        $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'watts' => ['nullable', 'numeric', 'min:0'],
            'kwh' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        EnergyReading::create([
            'device_id' => $request->device_id,
            'watts' => $request->watts,
            'kwh' => $request->kwh,
            'recorded_at' => $request->recorded_at ?? now(),
            'source' => 'measured',
            'measurement_type' => 'instant',
        ]);

        return response()->json(['status' => 'ok'], 201);
    }
}
