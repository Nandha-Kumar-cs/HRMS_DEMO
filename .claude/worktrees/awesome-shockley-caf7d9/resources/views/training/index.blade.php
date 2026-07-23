@extends('layouts.app')
@section('title','Training')
@section('breadcrumb')
<li class="breadcrumb-item active">Training</li>
@endsection
@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-700 mb-1" style="font-size:20px"><i class="fa fa-graduation-cap me-2 text-primary"></i>Training Center</h4>
        <p class="text-muted mb-0" style="font-size:13px">Browse and complete training modules assigned to your role.</p>
    </div>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('training.module.create') }}" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>New Module
    </a>
    @endif
</div>

@if($modules->isEmpty())
<div class="card page-card">
    <div class="card-body text-center py-5">
        <div style="font-size:48px;margin-bottom:12px">📚</div>
        <h5 class="fw-600">No training modules available</h5>
        <p class="text-muted">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('training.module.create') }}">Create your first module</a> to get started.
            @else
                Check back later — your administrator will assign training to your role.
            @endif
        </p>
    </div>
</div>
@else
<div class="row g-3">
    @foreach($modules as $module)
    <div class="col-md-6 col-lg-4">
        <div class="card page-card h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div style="width:40px;height:40px;background:var(--md-primary-pale);border-radius:var(--md-radius);
                                display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">
                        📖
                    </div>
                    @if(!$module->is_published)
                        <span class="md-pill md-pill-warning">Draft</span>
                    @elseif($module->progress_pct >= 100)
                        <span class="md-pill md-pill-success">✓ Complete</span>
                    @endif
                </div>
                <h6 class="fw-600 mt-2 mb-1">{{ $module->title }}</h6>
                <p class="text-muted mb-3" style="font-size:12.5px;flex:1">
                    {{ Str::limit($module->description, 100) ?: 'No description.' }}
                </p>

                {{-- Progress bar --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1" style="font-size:11px;color:var(--md-text-muted)">
                        <span>{{ $module->lessons_done }} / {{ $module->lessons_total }} lessons</span>
                        <span>{{ $module->progress_pct }}%</span>
                    </div>
                    <div style="height:6px;background:var(--md-border);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $module->progress_pct }}%;background:var(--md-primary);border-radius:3px;transition:width .5s"></div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-auto">
                    <a href="{{ route('training.show', $module) }}" class="btn btn-primary btn-sm flex-fill">
                        {{ $module->progress_pct > 0 ? 'Continue' : 'Start' }}
                    </a>
                    @if(auth()->user()->isAdmin())
                    <a href="{{ route('training.module.edit', $module) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-edit"></i>
                    </a>
                    <button class="btn btn-outline-danger btn-sm btn-delete"
                            data-url="{{ route('training.module.destroy', $module) }}">
                        <i class="fa fa-trash"></i>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
@push('scripts')
<script>
window.pageShortcuts = {
    'n': { label: 'New Module', action: function() {
        @if(auth()->user()->isAdmin())
        window.location = '{{ route('training.module.create') }}';
        @endif
    }}
};
</script>
@endpush
