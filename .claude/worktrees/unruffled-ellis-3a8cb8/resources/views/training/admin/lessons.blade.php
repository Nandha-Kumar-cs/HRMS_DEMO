@extends('layouts.app')
@section('title','Lessons — '.$trainingModule->title)
@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('training.index') }}" class="text-decoration-none">Training</a></li>
<li class="breadcrumb-item"><a href="{{ route('training.show', $trainingModule) }}" class="text-decoration-none">{{ $trainingModule->title }}</a></li>
<li class="breadcrumb-item active">Manage Lessons</li>
@endsection
@section('content')
<div class="row g-4">
    {{-- Existing lessons --}}
    <div class="col-lg-7">
        <div class="card page-card">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 fw-600">Lessons ({{ $lessons->count() }})</h6>
                <a href="{{ route('training.show', $trainingModule) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-eye me-1"></i>Preview
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($lessons as $i => $lesson)
                <div class="d-flex align-items-start gap-3 p-3" style="border-bottom:1px solid var(--md-border)" id="lesson-{{ $lesson->id }}">
                    <div style="width:28px;height:28px;background:var(--md-surface-2);border-radius:50%;
                                display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;
                                flex-shrink:0;color:var(--md-text-secondary);border:1px solid var(--md-border)">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-fill">
                        <div class="fw-600" style="font-size:13.5px">{{ $lesson->title }}</div>
                        @if($lesson->screenshot)
                        <div style="font-size:11px;color:var(--md-text-muted)"><i class="fa fa-image me-1"></i>Screenshot attached</div>
                        @endif
                        @if($lesson->content)
                        <div style="font-size:12px;color:var(--md-text-muted)">{{ Str::limit(strip_tags($lesson->content), 80) }}</div>
                        @endif
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0">
                        <button class="btn btn-xs btn-outline-primary btn-edit-lesson"
                                data-id="{{ $lesson->id }}"
                                data-title="{{ $lesson->title }}"
                                data-content="{{ $lesson->content }}"
                                data-order="{{ $lesson->sort_order }}">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-xs btn-outline-danger btn-delete"
                                data-url="{{ route('training.lesson.destroy', [$trainingModule, $lesson]) }}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-4">No lessons yet. Add one using the form.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Add/Edit lesson form --}}
    <div class="col-lg-5">
        <div class="card page-card">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-600" id="formTitle">Add New Lesson</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('training.lesson.store', $trainingModule) }}" method="POST"
                      enctype="multipart/form-data" id="lessonForm">
                    @csrf
                    <input type="hidden" name="_method" id="lessonMethod" value="POST">
                    <input type="hidden" name="_lesson_id" id="lessonId">

                    @if($errors->any())
                    <div class="alert alert-danger mb-3"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Lesson Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="lessonTitle" class="form-control" required
                               placeholder="e.g. Introduction to Payroll">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea name="content" id="lessonContent" class="form-control" rows="6"
                                  placeholder="Write the lesson content here. Plain text supported."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Screenshot / Visual</label>
                        <input type="file" name="screenshot" class="form-control" accept="image/*">
                        <div class="form-text">PNG, JPG — max 4MB. Displayed above lesson content.</div>
                        <div id="currentScreenshot" class="mt-2 d-none">
                            <small class="text-muted"><i class="fa fa-image me-1"></i>Current screenshot will be kept unless you upload a new one.</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Order</label>
                        <input type="number" name="sort_order" id="lessonOrder" class="form-control" min="0" value="{{ $lessons->count() + 1 }}">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i><span id="submitLabel">Add Lesson</span></button>
                        <button type="button" class="btn btn-light d-none" id="cancelEdit">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
// Switch form to edit mode
$('.btn-edit-lesson').on('click', function() {
    var btn = $(this);
    $('#formTitle').text('Edit Lesson');
    $('#submitLabel').text('Save Changes');
    $('#lessonTitle').val(btn.data('title'));
    $('#lessonContent').val(btn.data('content'));
    $('#lessonOrder').val(btn.data('order'));
    $('#currentScreenshot').removeClass('d-none');

    var id = btn.data('id');
    var url = '{{ route('training.lesson.update', [$trainingModule, '__id__']) }}'.replace('__id__', id);
    $('#lessonForm').attr('action', url);
    $('#lessonMethod').val('PUT');
    $('#lessonId').val(id);
    $('#cancelEdit').removeClass('d-none');
    $('html').animate({ scrollTop: $('#lessonForm').offset().top - 80 }, 300);
});

$('#cancelEdit').on('click', function() {
    $('#formTitle').text('Add New Lesson');
    $('#submitLabel').text('Add Lesson');
    $('#lessonForm')[0].reset();
    $('#lessonForm').attr('action', '{{ route('training.lesson.store', $trainingModule) }}');
    $('#lessonMethod').val('POST');
    $('#currentScreenshot').addClass('d-none');
    $(this).addClass('d-none');
});
</script>
@endpush
