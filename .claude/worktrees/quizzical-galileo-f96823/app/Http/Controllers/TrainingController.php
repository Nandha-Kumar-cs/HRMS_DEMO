<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\TrainingProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $modules = TrainingModule::with(['lessons'])
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $q->where('is_published', true)
                  ->where(function ($q2) use ($user) {
                      $q2->whereNull('role_access')
                         ->orWhereJsonContains('role_access', $user->role);
                  });
            })
            ->orderBy('title')
            ->get()
            ->map(function ($module) use ($user) {
                $total     = $module->lessons->count();
                $completed = $total ? TrainingProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $module->lessons->pluck('id'))
                    ->whereNotNull('completed_at')
                    ->count() : 0;
                $module->progress_pct   = $total ? round($completed / $total * 100) : 0;
                $module->lessons_total  = $total;
                $module->lessons_done   = $completed;
                return $module;
            });

        return view('training.index', compact('modules'));
    }

    public function show(TrainingModule $trainingModule)
    {
        $user = Auth::user();
        if (!$trainingModule->isAccessibleBy($user)) {
            abort(403);
        }

        $lessons = $trainingModule->lessons;
        $completedIds = TrainingProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->whereNotNull('completed_at')
            ->pluck('lesson_id')
            ->toArray();

        return view('training.show', compact('trainingModule', 'lessons', 'completedIds'));
    }

    public function lesson(TrainingModule $trainingModule, TrainingLesson $lesson)
    {
        $user = Auth::user();
        if (!$trainingModule->isAccessibleBy($user)) abort(403);
        if ($lesson->training_module_id !== $trainingModule->id) abort(404);

        $prev = $trainingModule->lessons->where('sort_order', '<', $lesson->sort_order)->last();
        $next = $trainingModule->lessons->where('sort_order', '>', $lesson->sort_order)->first();
        $isCompleted = $lesson->isCompletedBy($user->id);

        return view('training.lesson', compact('trainingModule', 'lesson', 'prev', 'next', 'isCompleted'));
    }

    public function markComplete(Request $request, TrainingModule $trainingModule, TrainingLesson $lesson)
    {
        $user = Auth::user();
        TrainingProgress::updateOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            ['completed_at' => now()]
        );
        return response()->json(['success' => true]);
    }

    // ── Admin: Module CRUD ────────────────────────────────────────

    public function createModule()
    {
        $roles = array_keys(config('magdyn.default_roles', []) + ['admin' => [], 'manager' => [], 'staff' => []]);
        return view('training.admin.create-module', compact('roles'));
    }

    public function storeModule(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string',
            'role_access'  => 'nullable|array',
            'is_published' => 'boolean',
        ]);

        $data['created_by']   = Auth::id();
        $data['role_access']  = $data['role_access'] ?? [];
        $data['is_published'] = $request->boolean('is_published');

        $module = TrainingModule::create($data);
        return redirect()->route('training.admin.module.lessons', $module)
            ->with('success', 'Module created. Now add lessons.');
    }

    public function editModule(TrainingModule $trainingModule)
    {
        $roles = ['admin', 'manager', 'staff'];
        return view('training.admin.edit-module', compact('trainingModule', 'roles'));
    }

    public function updateModule(Request $request, TrainingModule $trainingModule)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:200',
            'description'  => 'nullable|string',
            'role_access'  => 'nullable|array',
            'is_published' => 'boolean',
        ]);
        $data['role_access']  = $data['role_access'] ?? [];
        $data['is_published'] = $request->boolean('is_published');
        $trainingModule->update($data);
        return redirect()->route('training.index')->with('success', 'Module updated.');
    }

    public function destroyModule(TrainingModule $trainingModule)
    {
        foreach ($trainingModule->lessons as $lesson) {
            if ($lesson->screenshot) Storage::delete($lesson->screenshot);
        }
        $trainingModule->delete();
        return response()->json(['success' => true, 'message' => 'Module deleted.']);
    }

    public function moduleLessons(TrainingModule $trainingModule)
    {
        $lessons = $trainingModule->lessons;
        return view('training.admin.lessons', compact('trainingModule', 'lessons'));
    }

    public function storeLesson(Request $request, TrainingModule $trainingModule)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'screenshot' => 'nullable|image|max:4096',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('screenshot')) {
            $data['screenshot'] = $request->file('screenshot')->store('training/screenshots', 'public');
        }

        $data['sort_order'] = $data['sort_order'] ?? ($trainingModule->lessons()->max('sort_order') + 1);
        $trainingModule->lessons()->create($data);

        return redirect()->back()->with('success', 'Lesson added.');
    }

    public function updateLesson(Request $request, TrainingModule $trainingModule, TrainingLesson $lesson)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'content'    => 'nullable|string',
            'screenshot' => 'nullable|image|max:4096',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('screenshot')) {
            if ($lesson->screenshot) Storage::delete($lesson->screenshot);
            $data['screenshot'] = $request->file('screenshot')->store('training/screenshots', 'public');
        }

        $lesson->update($data);
        return redirect()->back()->with('success', 'Lesson updated.');
    }

    public function destroyLesson(TrainingModule $trainingModule, TrainingLesson $lesson)
    {
        if ($lesson->screenshot) Storage::delete($lesson->screenshot);
        $lesson->delete();
        return response()->json(['success' => true, 'message' => 'Lesson deleted.']);
    }
}
