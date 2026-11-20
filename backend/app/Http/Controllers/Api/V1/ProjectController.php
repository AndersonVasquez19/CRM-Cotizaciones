<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectTask;
use App\Services\ProjectConformityPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::with([
            'client:id,business_name,document_number',
            'site:id,site_name',
            'quote:id,quote_number,version,total,currency',
            'leadTechnician:id,name',
        ])->withCount('tasks');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest('id')->paginate(20));
    }

    public function show(Project $project): JsonResponse
    {
        $project->load([
            'client',
            'site',
            'quote',
            'companyProfile',
            'leadTechnician:id,name',
            'tasks.assignee:id,name',
            'materials.catalogItem:id,sku,name',
            'milestones.payments',
        ]);

        return response()->json(['data' => $project]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['planificado', 'en_ejecucion', 'pausado', 'completado'])],
            'lead_technician_id' => ['nullable', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'conformity_notes' => ['nullable', 'string'],
        ]);

        if (($validated['status'] ?? null) === 'completado' && $project->completed_at === null) {
            $validated['completed_at'] = now();
            $validated['progress_percent'] = 100;
        }

        $project->update($validated);

        return response()->json(['data' => $project->fresh(['tasks', 'materials', 'leadTechnician:id,name'])]);
    }

    public function toggleTask(Project $project, ProjectTask $task): JsonResponse
    {
        abort_if($task->project_id !== $project->id, 404);

        $completed = ! $task->is_completed;
        $task->update([
            'is_completed' => $completed,
            'completed_at' => $completed ? now() : null,
        ]);

        $totalTasks = $project->tasks()->count();
        $doneTasks = $project->tasks()->where('is_completed', true)->count();
        $progress = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        $project->update([
            'progress_percent' => $progress,
            'status' => $progress === 100 ? 'completado' : ($progress > 0 ? 'en_ejecucion' : $project->status),
            'completed_at' => $progress === 100 ? now() : null,
        ]);

        return response()->json([
            'data' => $project->fresh(['tasks', 'materials']),
        ]);
    }

    public function updateMaterial(Request $request, Project $project, ProjectMaterial $material): JsonResponse
    {
        abort_if($material->project_id !== $project->id, 404);

        $validated = $request->validate([
            'used_qty' => ['sometimes', 'numeric', 'min:0'],
            'serial_numbers' => ['nullable', 'string'],
        ]);

        $material->update($validated);

        return response()->json(['data' => $material]);
    }

    public function conformityPdf(Project $project, ProjectConformityPdfService $service): Response
    {
        return $service->download($project);
    }
}
