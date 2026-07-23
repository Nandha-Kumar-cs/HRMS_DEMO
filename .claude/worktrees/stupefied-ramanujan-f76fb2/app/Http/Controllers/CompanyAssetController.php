<?php

namespace App\Http\Controllers;

use App\Models\AssetAssignment;
use App\Models\CompanyAsset;
use App\Models\Employee;
use App\Models\NoDueCertificate;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyAssetController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(CompanyAsset::with('currentAssignment.employee')->select('company_assets.*'))
                ->addColumn('type_label', fn($a) => CompanyAsset::$types[$a->asset_type] ?? ucfirst($a->asset_type))
                ->addColumn('status_badge', fn($a) => $a->status_badge)
                ->addColumn('assigned_to', fn($a) => $a->currentAssignment?->employee?->full_name ?? '-')
                ->addColumn('action', function ($a) {
                    $buttons = '<a href="' . route('assets.edit', $a) . '" class="btn btn-xs btn-outline-primary"><i class="fa fa-edit"></i></a> ';
                    if ($a->status === 'available') {
                        $buttons .= '<a href="' . route('assets.assign', $a) . '" class="btn btn-xs btn-outline-success" title="Assign"><i class="fa fa-user-plus"></i></a> ';
                    }
                    if ($a->status === 'assigned') {
                        $buttons .= '<a href="' . route('assets.return-form', $a) . '" class="btn btn-xs btn-outline-warning" title="Return"><i class="fa fa-undo"></i></a> ';
                    }
                    $buttons .= '<button class="btn btn-xs btn-outline-danger btn-delete" data-url="' . route('assets.destroy', $a) . '"><i class="fa fa-trash"></i></button>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
        return view('company-assets.index');
    }

    public function create()
    {
        return view('company-assets.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_name'    => 'required|string|max:255',
            'asset_type'    => 'required|string',
            'serial_number' => 'nullable|string|max:100',
            'description'   => 'nullable|string',
        ]);

        CompanyAsset::create($data);
        return redirect()->route('assets.index')->with('success', 'Asset added successfully.');
    }

    public function edit(CompanyAsset $asset)
    {
        return view('company-assets.edit', compact('asset'));
    }

    public function update(Request $request, CompanyAsset $asset)
    {
        $data = $request->validate([
            'asset_name'    => 'required|string|max:255',
            'asset_type'    => 'required|string',
            'serial_number' => 'nullable|string|max:100',
            'description'   => 'nullable|string',
            'status'        => 'required|in:available,assigned,returned,damaged',
        ]);

        $asset->update($data);
        return redirect()->route('assets.index')->with('success', 'Asset updated.');
    }

    public function destroy(CompanyAsset $asset)
    {
        $asset->delete();
        return response()->json(['success' => true, 'message' => 'Asset deleted.']);
    }

    public function assign(CompanyAsset $asset)
    {
        $employees = Employee::where('status', 'active')->orderBy('full_name')->get();
        return view('company-assets.assign', compact('asset', 'employees'));
    }

    public function storeAssignment(Request $request, CompanyAsset $asset)
    {
        $data = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'issue_date'        => 'required|date',
            'condition_on_issue'=> 'nullable|string|max:255',
            'remarks'           => 'nullable|string|max:500',
        ]);

        AssetAssignment::create(array_merge($data, ['company_asset_id' => $asset->id]));
        $asset->update(['status' => 'assigned']);

        return redirect()->route('assets.index')->with('success', 'Asset assigned successfully.');
    }

    public function returnForm(CompanyAsset $asset)
    {
        $assignment = $asset->currentAssignment()->with('employee')->first();
        return view('company-assets.return', compact('asset', 'assignment'));
    }

    public function processReturn(Request $request, CompanyAsset $asset)
    {
        $data = $request->validate([
            'return_date'        => 'required|date',
            'condition_on_return'=> 'nullable|string|max:255',
            'remarks'            => 'nullable|string|max:500',
        ]);

        $assignment = $asset->currentAssignment;
        $assignment->update($data);
        $asset->update(['status' => 'returned']);

        return redirect()->route('assets.index')->with('success', 'Asset return recorded.');
    }

    // No Due Certificate
    public function noDueIndex()
    {
        $certificates = NoDueCertificate::with('employee')->latest()->paginate(15);
        return view('company-assets.no-due.index', compact('certificates'));
    }

    public function noDueCreate()
    {
        $employees = Employee::orderBy('full_name')->get();
        return view('company-assets.no-due.create', compact('employees'));
    }

    public function noDueStore(Request $request)
    {
        $request->validate(['employee_id' => 'required|exists:employees,id']);

        $employee = Employee::with('currentAssets.asset')->find($request->employee_id);
        $pending  = $employee->currentAssets;

        $cert = NoDueCertificate::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'generated_date' => now()->toDateString(),
                'status'         => $pending->isEmpty() ? 'approved' : 'pending',
                'remarks'        => $request->remarks,
            ]
        );

        return redirect()->route('no-due.show', $cert)->with('success', 'No Due Certificate generated.');
    }

    public function noDueShow(NoDueCertificate $certificate)
    {
        $certificate->load('employee.currentAssets.asset');
        return view('company-assets.no-due.show', compact('certificate'));
    }

    public function noDueApprove(NoDueCertificate $certificate)
    {
        $certificate->update(['status' => 'approved']);
        return redirect()->route('no-due.show', $certificate)->with('success', 'Certificate approved.');
    }
}
