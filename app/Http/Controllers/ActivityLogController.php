<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only activity history (admin only). There are no edit or delete routes. */
class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $from = $this->parseDate($request->query('date_from'));
        $to = $this->parseDate($request->query('date_to'));
        $module = $request->query('module');
        $action = $request->query('action');
        $userId = $request->query('user');

        $logs = ActivityLog::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")->orWhere('user_name', 'like', "%{$search}%");
            }))
            ->when(is_numeric($userId), fn ($q) => $q->where('user_id', (int) $userId))
            ->when(is_string($module) && $module !== '', fn ($q) => $q->where('module', $module))
            ->when(is_string($action) && $action !== '', fn ($q) => $q->where('action', $action))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->endOfDay()))
            ->latest()->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}
