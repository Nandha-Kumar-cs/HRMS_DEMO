@extends('layouts.app')
@section('title', $trainingModule->title)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('training.index') }}" class="text-decoration-none">Training</a></li>
<li class="breadcrumb-item active">{{ $trainingModule->title }}</li>
@endsection
@section('content')
<div class="row g-4">
    {{-- Module info --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-body">
                <h5 class="fw-600 mb-1">{{ $trainingModule->title }}</h5>
                <p class="text-muted mb-3" style="font-size:13px">{{ $trainingModule->description }}</p>

                @php
                    $total = $lessons->count();
                    $done  = count($completedIds);
                    $pct   = $total ? round($done / $total * 100) : 0;
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:12px;color:var(--md-text-muted)">
                        <span>{{ $done }} / {{ $total }} lessons complete</span>
                        <span>{{ $pct }}%</span>
                    </div>
                    <div style="height:8px;background:var(--md-border);border-radius:4px;overflow:hidden">
                        <div style="height:100%;width:{{ $pct }}%;background:var(--md-primary);border-radius:4px;transition:width .5s"></div>
                    </div>
                </div>

                @if($pct >= 100)
                <div class="md-pill md-pill-success w-100 justify-content-center py-2">
                    🎉 Module Complete!
                </div>
                @endif

                @if(auth()->user()->isAdmin())
                <hr>
                <a href="{{ route('training.module.lessons', $trainingModule) }}" class="btn btn-outline-primary btn-sm w-100">
                    <i class="fa fa-edit me-1"></i> Manage Lessons
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Lesson list --}}
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-600">Lessons</h6>
            </div>
            <div class="card-body p-0">
                @forelse($lessons as $i => $lesson)
                @php $isCompleted = in_array($lesson->id, $completedIds); @endphp
                <a href="{{ route('training.lesson', [$trainingModule, $lesson]) }}"
                   class="d-flex align-items-center gap-3 p-3 text-decoration-none"
                   style="border-bottom:1px solid var(--md-border);color:var(--md-text);transition:background .15s"
                   onmouseover="this.style.background='var(--md-primary-pale)'"
                   onmouseout="this.style.background=''">
                    <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                background:{{ $isCompleted ? 'var(--md-success-pale)' : 'var(--md-surface-2)' }};
                                color:{{ $isCompleted ? 'var(--md-success)' : 'var(--md-text-muted)' }};
                                border:1.5px solid {{ $isCompleted ? 'var(--md-success)' : 'var(--md-border)' }};
                                font-size:13px;font-weight:700">
                        {{ $isCompleted ? '✓' : ($i + 1) }}
                    </div>
                    <div class="flex-fill">
                        <div class="fw-600" style="font-size:13.5px">{{ $lesson->title }}</div>
                        @if($lesson->screenshot)
                        <div style="font-size:11px;color:var(--md-text-muted)"><i class="fa fa-image me-1"></i>Has screenshot</div>
                        @endif
                    </div>
                    <i class="fa fa-chevron-right" style="color:var(--md-text-muted);font-size:12px"></i>
                </a>
                @empty
                <p class="text-muted text-center py-4">No lessons yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
