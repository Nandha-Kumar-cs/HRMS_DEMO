@extends('layouts.app')
@section('title', $lesson->title)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('training.index') }}" class="text-decoration-none">Training</a></li>
<li class="breadcrumb-item"><a href="{{ route('training.show', $trainingModule) }}" class="text-decoration-none">{{ $trainingModule->title }}</a></li>
<li class="breadcrumb-item active">{{ $lesson->title }}</li>
@endsection
@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-semibold" style="font-size:16px">{{ $lesson->title }}</h5>
                @if($isCompleted)
                <span class="md-pill md-pill-success"><i class="fa fa-check me-1"></i>Completed</span>
                @endif
            </div>
            <div class="card-body">
                {{-- Screenshot --}}
                @if($lesson->screenshot)
                <div class="mb-4 text-center">
                    <img src="{{ Storage::url($lesson->screenshot) }}"
                         alt="{{ $lesson->title }} screenshot"
                         class="img-fluid rounded"
                         style="max-height:420px;border:1px solid var(--md-border);border-radius:var(--md-radius)">
                    <div class="mt-2 text-muted" style="font-size:11px">
                        <i class="fa fa-image me-1"></i>Visual reference for this lesson
                    </div>
                </div>
                @endif

                {{-- Content --}}
                <div class="lesson-content" style="font-size:14px;line-height:1.8;color:var(--md-text)">
                    {!! $lesson->content !!}
                </div>

                @if(!$lesson->content && !$lesson->screenshot)
                <p class="text-muted text-center py-4">No content for this lesson yet.</p>
                @endif
            </div>
            <div class="card-footer bg-white py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex gap-2">
                    @if($prev)
                    <a href="{{ route('training.lesson', [$trainingModule, $prev]) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fa fa-arrow-left me-1"></i> Previous
                    </a>
                    @endif
                    @if($next)
                    <a href="{{ route('training.lesson', [$trainingModule, $next]) }}" class="btn btn-outline-primary btn-sm" id="nextBtn">
                        Next <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                    @endif
                </div>
                @if(!$isCompleted)
                <button id="markCompleteBtn" class="btn btn-success btn-sm">
                    <i class="fa fa-check me-1"></i>Mark as Complete
                </button>
                @else
                <span class="text-muted" style="font-size:12.5px"><i class="fa fa-check-circle text-success me-1"></i>Completed</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Sidebar: lesson navigation --}}
    <div class="col-lg-4">
        <div class="card page-card">
            <div class="card-header bg-white py-2">
                <div class="fw-600" style="font-size:13px">{{ $trainingModule->title }}</div>
            </div>
            <div style="max-height:400px;overflow-y:auto">
                @foreach($trainingModule->lessons as $i => $l)
                <a href="{{ route('training.lesson', [$trainingModule, $l]) }}"
                   class="d-flex align-items-center gap-2 p-2 px-3 text-decoration-none"
                   style="border-bottom:1px solid var(--md-border);
                          color:{{ $l->id === $lesson->id ? 'var(--md-primary)' : 'var(--md-text)' }};
                          background:{{ $l->id === $lesson->id ? 'var(--md-primary-pale)' : '' }};
                          font-size:13px">
                    <span style="width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                 background:{{ $l->isCompletedBy(auth()->id()) ? 'var(--md-success)' : 'var(--md-border)' }};
                                 color:{{ $l->isCompletedBy(auth()->id()) ? '#fff' : 'var(--md-text-muted)' }};
                                 font-size:10px;font-weight:700;flex-shrink:0">
                        {{ $l->isCompletedBy(auth()->id()) ? '✓' : ($i+1) }}
                    </span>
                    {{ $l->title }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$('#markCompleteBtn').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Saving...');
    $.ajax({
        url: '{{ route('training.lesson.complete', [$trainingModule, $lesson]) }}',
        type: 'POST',
        success: function() {
            btn.closest('.card-footer').find('#markCompleteBtn').replaceWith(
                '<span class="text-muted" style="font-size:12.5px"><i class="fa fa-check-circle text-success me-1"></i>Completed</span>'
            );
            window.mdToast('Lesson marked as complete!', 'success');
            @if($next)
            setTimeout(function() { window.location = '{{ route('training.lesson', [$trainingModule, $next]) }}'; }, 900);
            @endif
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fa fa-check me-1"></i>Mark as Complete');
            window.mdToast('Could not save. Please try again.', 'error');
        }
    });
});

window.pageShortcuts = {
    @if($next)
    'arrowright': { label: 'Next Lesson', action: function() { window.location = '{{ route('training.lesson', [$trainingModule, $next]) }}'; } },
    @endif
    @if($prev)
    'arrowleft':  { label: 'Previous Lesson', action: function() { window.location = '{{ route('training.lesson', [$trainingModule, $prev]) }}'; } },
    @endif
};
</script>
@endpush
