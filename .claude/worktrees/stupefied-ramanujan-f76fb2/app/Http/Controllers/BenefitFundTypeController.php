<?php

namespace App\Http\Controllers;

use App\Models\BenefitFundType;
use Illuminate\Http\Request;

class BenefitFundTypeController extends Controller
{
    public function index()
    {
        $types = BenefitFundType::withCount('employeeBenefits')->orderBy('name')->get();
        return view('benefit-fund-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:benefit_fund_types,name',
            'description' => 'nullable|string|max:1000',
            'color'       => 'required|string|max:20',
            'status'      => 'required|in:active,inactive',
        ]);
        $type = BenefitFundType::create($data);
        return response()->json(['success' => true, 'message' => 'Benefit fund type created.', 'data' => $type]);
    }

    public function update(Request $request, BenefitFundType $benefitFundType)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:benefit_fund_types,name,' . $benefitFundType->id,
            'description' => 'nullable|string|max:1000',
            'color'       => 'required|string|max:20',
            'status'      => 'required|in:active,inactive',
        ]);
        $benefitFundType->update($data);
        return response()->json(['success' => true, 'message' => 'Benefit fund type updated.', 'data' => $benefitFundType]);
    }

    public function destroy(BenefitFundType $benefitFundType)
    {
        if ($benefitFundType->employeeBenefits()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete — there are employee benefits using this type.',
            ], 422);
        }
        $benefitFundType->delete();
        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }

    public function edit(BenefitFundType $benefitFundType)
    {
        return response()->json($benefitFundType);
    }
}
