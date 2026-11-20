<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\SiteSurvey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSurveyController extends Controller
{
    public function index(Lead $lead): JsonResponse
    {
        $surveys = $lead->surveys()->with(['user:id,name', 'photos'])->get();

        return response()->json(['data' => $surveys]);
    }

    public function store(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'cable_meters' => ['required', 'numeric', 'min:0'],
            'conduit_type' => ['nullable', 'string', 'max:60'],
            'max_height_meters' => ['numeric', 'min:0'],
            'power_requirements' => ['nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string'],
            'surveyed_at' => ['nullable', 'date'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        $survey = SiteSurvey::create([
            'lead_id' => $lead->id,
            'user_id' => $request->user()->id,
            'cable_meters' => $validated['cable_meters'],
            'conduit_type' => $validated['conduit_type'] ?? null,
            'max_height_meters' => $validated['max_height_meters'] ?? 0,
            'power_requirements' => $validated['power_requirements'] ?? null,
            'observations' => $validated['observations'] ?? null,
            'surveyed_at' => $validated['surveyed_at'] ?? now(),
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store("surveys/{$lead->id}", 'public');
                $survey->photos()->create([
                    'file_path' => $path,
                    'caption' => $photo->getClientOriginalName(),
                    'created_at' => now(),
                ]);
            }
        }

        $lead->activities()->create([
            'user_id' => $request->user()->id,
            'activity_type' => 'visita_tecnica',
            'subject' => "Levantamiento Técnico ({$survey->cable_meters}m / {$survey->conduit_type})",
            'description' => $survey->observations,
            'completed_at' => now(),
        ]);

        return response()->json([
            'data' => $survey->load(['user:id,name', 'photos']),
        ], 201);
    }
}
