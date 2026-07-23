<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\Facades\DataTables;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(SalaryComponent::query())
                ->addColumn('type_badge', fn($c) => '<span class="badge bg-' . ($c->type === 'allowance' ? 'success' : 'danger') . '">' . ucfirst($c->type) . '</span>')
                ->addColumn('formula', fn($c) => $c->formula)
                ->addColumn('action', fn($c) => view('salary-components.partials.action', ['component' => $c])->render())
                ->rawColumns(['type_badge', 'action'])
                ->make(true);
        }
        return view('salary-components.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:allowance,deduction',
            'calculation_type' => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0',
        ]);
        $component = SalaryComponent::create($data);
        Cache::forget('salary_components_all');
        ActivityLog::record('created', 'SalaryComponent',
            "Created salary component: {$component->name} ({$component->type}) — {$component->formula}"
        );
        return response()->json(['success' => true, 'message' => 'Component created.', 'data' => $component, 'formula' => $component->formula]);
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'type'             => 'required|in:allowance,deduction',
            'calculation_type' => 'required|in:percentage,fixed',
            'value'            => 'required|numeric|min:0',
        ]);
        $salaryComponent->update($data);
        Cache::forget('salary_components_all');
        ActivityLog::record('updated', 'SalaryComponent',
            "Updated salary component: {$salaryComponent->name} ({$salaryComponent->type}) — {$salaryComponent->formula}"
        );
        return response()->json(['success' => true, 'message' => 'Component updated.', 'data' => $salaryComponent, 'formula' => $salaryComponent->formula]);
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        $name = $salaryComponent->name;
        $type = $salaryComponent->type;
        $salaryComponent->delete();
        Cache::forget('salary_components_all');
        ActivityLog::record('deleted', 'SalaryComponent', "Deleted salary component: {$name} ({$type})");
        return response()->json(['success' => true, 'message' => 'Component deleted.']);
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        return response()->json($salaryComponent);
    }
}
