@extends('layouts.app')
@section('title', 'Activity Logs')
@section('page-title', 'Activity Logs')

@section('content')
    @php
        $actionPill = [
            'create' => 'pill-ok', 'login' => 'pill-ok', 'activate' => 'pill-ok', 'stock_in' => 'pill-ok', 'sale' => 'pill-ok',
            'update' => 'pill-warn', 'reset_password' => 'pill-warn', 'export_pdf' => 'pill-warn', 'export_excel' => 'pill-warn', 'print' => 'pill-warn',
            'delete' => 'pill-danger', 'deactivate' => 'pill-danger', 'damaged' => 'pill-danger', 'stock_out' => 'pill-danger',
        ];
    @endphp

    <div class="page-head">
        <div><h2 class="section-heading">Activity Logs</h2></div>
    </div>

    <form method="GET" action="{{ route('activity-logs.index') }}" class="card-box filter-box mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Description or user name">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small">User</label>
                <select name="user" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($users as $u)<option value="{{ $u->id }}" @selected((string) request('user') === (string) $u->id)>{{ $u->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($modules as $m)<option value="{{ $m }}" @selected(request('module') === $m)>{{ $m }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-6">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach ($actions as $a)<option value="{{ $a }}" @selected(request('action') === $a)>{{ ucwords(str_replace('_', ' ', $a)) }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-1 col-6"><label class="form-label small">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm"></div>
            <div class="col-lg-1 col-6"><label class="form-label small">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm"></div>
            <div class="col-12 d-flex gap-2">
                <button class="btn btn-berry btn-sm px-3"><i class="fa-solid fa-magnifying-glass me-1"></i>Apply</button>
                <a href="{{ route('activity-logs.index') }}" class="btn btn-light btn-sm px-3">Reset</a>
            </div>
        </div>
    </form>

    <div class="card-box p-0 overflow-hidden">
        @if ($logs->isEmpty())
            <div class="empty-state py-5"><i class="fa-solid fa-clock-rotate-left"></i>No activities found.</div>
        @else
            <div class="table-responsive">
                <table class="table table-berry align-middle mb-0">
                    <thead><tr><th>Log ID</th><th>User</th><th>Activity</th><th>Module</th><th>Action</th><th>Date &amp; Time</th></tr></thead>
                    <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="text-muted text-nowrap">#{{ $log->id }}</td>
                            <td class="fw-medium text-nowrap">{{ $log->user_name }}</td>
                            <td>{{ $log->description }}</td>
                            <td class="text-nowrap">{{ $log->module }}</td>
                            <td><span class="pill {{ $actionPill[$log->action] ?? 'pill-berry' }}">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span></td>
                            <td class="text-nowrap">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">Showing {{ $logs->firstItem() }}-{{ $logs->lastItem() }} of {{ number_format($logs->total()) }}</small>
                    {{ $logs->links() }}
                </div>
            @else
                <div class="p-3 border-top"><small class="text-muted">{{ number_format($logs->total()) }} {{ \Illuminate\Support\Str::plural('record', $logs->total()) }}</small></div>
            @endif
        @endif
    </div>
@endsection
