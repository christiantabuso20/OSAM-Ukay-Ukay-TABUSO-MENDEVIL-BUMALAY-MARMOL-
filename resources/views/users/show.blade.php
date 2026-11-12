@extends('layouts.app')
@section('title', 'User Details')
@section('page-title', 'User Management')

@section('content')
    <div class="page-head">
        <div><h2 class="section-heading">{{ $account->name }}</h2><p class="text-muted mb-0">{{ '@'.$account->username }}</p></div>
        <a href="{{ route('users.index') }}" class="btn btn-light border"><i class="fa-solid fa-arrow-left me-2"></i>Back to Users</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><x-stat-card icon="fa-receipt" label="Sales Recorded" :value="number_format($salesCount)" hint="Transactions" /></div>
        <div class="col-md-4"><x-stat-card icon="fa-peso-sign" label="Total Sales Amount" :value="'₱'.number_format($salesTotal, 2)" hint="From their sales" tone="rose" /></div>
        <div class="col-md-4"><x-stat-card icon="fa-boxes-stacked" label="Stock Movements" :value="number_format($movementCount)" hint="Recorded by this user" tone="dark" /></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Account Details</h3></div>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted fw-normal">User ID</dt><dd class="col-sm-8">#{{ $account->id }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">Full Name</dt><dd class="col-sm-8">{{ $account->name }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">Username</dt><dd class="col-sm-8">{{ $account->username }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">Email</dt><dd class="col-sm-8">{{ $account->email }}</dd>
                    <dt class="col-sm-4 text-muted fw-normal">Role</dt><dd class="col-sm-8"><span class="pill {{ $account->isAdmin() ? 'pill-berry' : 'pill-warn' }}">{{ ucfirst($account->role) }}</span></dd>
                    <dt class="col-sm-4 text-muted fw-normal">Status</dt><dd class="col-sm-8"><span class="pill {{ $account->is_active ? 'pill-ok' : 'pill-danger' }}">{{ $account->is_active ? 'Active' : 'Deactivated' }}</span></dd>
                    <dt class="col-sm-4 text-muted fw-normal">Date Created</dt><dd class="col-sm-8">{{ $account->created_at->format('M d, Y h:i A') }}</dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card-box h-100">
                <div class="card-box-header"><h3>Recent Activity</h3><a href="{{ route('activity-logs.index', ['user' => $account->id]) }}" class="small">View all</a></div>
                @if ($activities->isEmpty())
                    <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i>No activity recorded for this user yet.</div>
                @else
                    <ul class="feed-list">
                        @foreach ($activities as $log)
                            <li>
                                <div class="feed-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                                <div class="flex-grow-1">
                                    <div class="small text-muted">{{ $log->module }}</div>
                                    <div>{{ $log->description }}</div>
                                </div>
                                <small class="text-muted text-nowrap">{{ $log->created_at->format('M d, h:i A') }}</small>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection
