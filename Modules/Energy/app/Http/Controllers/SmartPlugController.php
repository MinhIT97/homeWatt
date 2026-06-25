<?php

namespace Modules\Energy\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Energy\Models\EnergyReading;

class SmartPlugController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $apiKey = $request->bearerToken();
        $validKey = config('services.smartplug.api_key');

        if (! $validKey || ! is_string($apiKey) || ! hash_equals($validKey, $apiKey)) {
            Log::warning('Smartplug auth failed', [
                'ip' => $request->ip(),
                'path' => $request->path(),
                'has_token' => ! empty($apiKey),
            ]);

            return response()->json(['error' => __('messages.unauthorized')], 401);
        }

        $request->validate([
            'device_id' => ['required', 'exists:devices,id'],
            'watts' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'kwh' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'recorded_at' => ['nullable', 'date', 'before_or_equal:now'],
            'measurement_type' => ['nullable', 'string', 'in:instant,cumulative'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
        ]);

        $deviceId = $request->integer('device_id');
        $idempotencyKey = $request->input('idempotency_key');
        $recordedAt = $request->input('recorded_at') ?? now();

        // Reject duplicate readings (same device + same recorded_at minute)
        $duplicate = EnergyReading::where('device_id', $deviceId)
            ->whereBetween('recorded_at', [
                Carbon::parse($recordedAt)->copy()->startOfMinute(),
                Carbon::parse($recordedAt)->copy()->endOfMinute(),
            ])
            ->exists();

        if ($duplicate && ! $idempotencyKey) {
            return response()->json([
                'error' => 'duplicate_reading',
                'message' => 'A reading for this device and minute already exists',
            ], 409);
        }

        // Idempotency check via key
        if ($idempotencyKey) {
            $exists = EnergyReading::where('device_id', $deviceId)
                ->where('idempotency_key', $idempotencyKey)
                ->exists();

            if ($exists) {
                return response()->json(['status' => 'duplicate_ignored'], 200);
            }
        }

        EnergyReading::create([
            'device_id' => $deviceId,
            'watts' => $request->watts,
            'kwh' => $request->kwh,
            'recorded_at' => $recordedAt,
            'source' => 'measured',
            'measurement_type' => $request->input('measurement_type', 'instant'),
            'idempotency_key' => $idempotencyKey,
        ]);

        return response()->json(['status' => 'ok'], 201);
    }
}
