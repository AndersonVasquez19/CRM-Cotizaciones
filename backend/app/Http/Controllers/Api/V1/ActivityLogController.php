<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('viewLogs', User::class), 403, 'Acceso denegado.');

        $logs = ActivityLog::with('user:id,name,email,role')
            ->latest('id')
            ->paginate(30);

        return response()->json($logs);
    }
}
